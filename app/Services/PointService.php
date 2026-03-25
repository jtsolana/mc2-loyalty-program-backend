<?php

namespace App\Services;

use App\Enums\PointRuleType;
use App\Enums\RewardStatus;
use App\Enums\TransactionType;
use App\Models\LoyaltyPoint;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\Purchase;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\RewardRule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class PointService
{
    /**
     * Calculate points using the active rule, supporting both spend-based and per-item types.
     *
     * @param  float  $amount  Total amount spent (for spend_based rules).
     * @param  int  $itemCount  Number of items/drinks ordered (for per_item rules).
     */
    public function calculatePoints(float $amount = 0, int $itemCount = 0): int
    {
        $rule = PointRule::where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $rule) {
            return 0;
        }

        if ($rule->type === PointRuleType::PerItem) {
            return $rule->calculatePoints(itemCount: $itemCount);
        }

        return $rule->calculatePoints(amount: $amount);
    }

    public function earnPoints(User $customer, int $points, string $description, ?User $staff = null, ?Purchase $purchase = null): PointTransaction
    {
        return DB::transaction(function () use ($customer, $points, $description, $staff, $purchase) {
            $loyaltyPoint = LoyaltyPoint::firstOrCreate(
                ['user_id' => $customer->id],
                ['total_points' => 0, 'lifetime_points' => 0]
            );

            $loyaltyPoint->increment('total_points', $points);
            $loyaltyPoint->increment('lifetime_points', $points);
            $loyaltyPoint->refresh();

            Log::info("Loyalty points updated for user_id={$customer->id}: +{$points} points (Total: {$loyaltyPoint->total_points})");

            $transaction = PointTransaction::create([
                'user_id' => $customer->id,
                'staff_id' => $staff?->id,
                'type' => TransactionType::Earn->value,
                'points' => $points,
                'balance_after' => $loyaltyPoint->total_points,
                'description' => $description,
                'reference_type' => $purchase ? Purchase::class : null,
                'reference_id' => $purchase?->id,
            ]);

            Log::info("Earned {$points} points for user_id={$customer->id}. Reason: {$transaction}");

            $this->checkAndNotifyForReedemableRewards($customer);

            return $transaction;
        });
    }

    private function checkAndNotifyForReedemableRewards(User $customer): void
    {
        $rewardRules = RewardRule::where('is_active', true)
            ->where('points_required', '<=', $customer->loyaltyPoint->total_points)
            ->count();

        if ($rewardRules > 0) {
            $messaging = Firebase::messaging();
            $mobileScheme = config('app.mobile_scheme');

            foreach ($customer->devices as $device) {
                try {
                    $message = CloudMessage::new()
                        ->withToken($device->fcm_token)
                        ->withNotification(
                            Notification::create(
                                '🎉 Reward Unlocked!',
                                'You are now eligible to claim your reward!'
                            )
                        )
                        ->withData([
                            'type' => 'reward',
                            'user_id' => (string) $customer->hashed_id,
                            'deep_link' => "{$mobileScheme}rewards",
                        ]);

                    $messaging->send($message);
                } catch (NotFound $e) {
                    \Sentry\captureException($e);
                    $device->delete();
                }
            }
        }
    }

    public function claimReward(User $customer, RewardRule $rule, LoyaltyPoint $loyaltyPoint, int $claimAmount): Reward
    {
        $reward = Reward::create([
            'user_id' => $customer->id,
            'reward_rule_id' => $rule->id,
            'points_deducted' => ($rule->points_required * $claimAmount),
            'status' => RewardStatus::Pending->value,
            'expires_at' => Carbon::now()->addDays($rule->expires_in_days),
        ]);

        $loyaltyPoint->decrement('total_points', ($rule->points_required * $claimAmount));
        $loyaltyPoint->refresh();

        PointTransaction::create([
            'user_id' => $customer->id,
            'type' => TransactionType::Reward->value,
            'points' => -($rule->points_required * $claimAmount),
            'balance_after' => $loyaltyPoint->total_points,
            'description' => "Reward claimed: {$rule->reward_title} ({$claimAmount}x)",
            'reference_type' => Reward::class,
            'reference_id' => $reward->id,
        ]);

        return $reward;
    }

    public function redeemPoints(User $customer, int $points, User $staff, ?Purchase $purchase = null): Redemption
    {
        return DB::transaction(function () use ($customer, $points, $staff, $purchase) {
            $loyaltyPoint = $customer->loyaltyPoint;

            if (! $loyaltyPoint || $loyaltyPoint->total_points < $points) {
                throw new \RuntimeException('Insufficient points balance.');
            }

            $discountAmount = $this->calculateDiscountForPoints($points);

            $redemption = Redemption::create([
                'user_id' => $customer->id,
                'staff_id' => $staff->id,
                'purchase_id' => $purchase?->id,
                'points_used' => $points,
                'discount_amount' => $discountAmount,
                'status' => 'applied',
            ]);

            $loyaltyPoint->decrement('total_points', $points);
            $loyaltyPoint->refresh();

            PointTransaction::create([
                'user_id' => $customer->id,
                'staff_id' => $staff->id,
                'type' => TransactionType::Redeem->value,
                'points' => -$points,
                'balance_after' => $loyaltyPoint->total_points,
                'description' => "Redeemed {$points} points for ₱{$discountAmount} discount",
                'reference_type' => Redemption::class,
                'reference_id' => $redemption->id,
            ]);

            return $redemption;
        });
    }

    public function adjustPoints(User $customer, int $points, string $description, User $admin): PointTransaction
    {
        return DB::transaction(function () use ($customer, $points, $description, $admin) {
            $loyaltyPoint = LoyaltyPoint::firstOrCreate(
                ['user_id' => $customer->id],
                ['total_points' => 0, 'lifetime_points' => 0]
            );

            if ($points > 0) {
                $loyaltyPoint->increment('total_points', $points);
                $loyaltyPoint->increment('lifetime_points', $points);
            } else {
                $absPoints = abs($points);
                if ($loyaltyPoint->total_points < $absPoints) {
                    throw new \RuntimeException('Cannot reduce points below zero.');
                }
                $loyaltyPoint->decrement('total_points', $absPoints);
            }

            $loyaltyPoint->refresh();

            return PointTransaction::create([
                'user_id' => $customer->id,
                'staff_id' => $admin->id,
                'type' => TransactionType::Adjust->value,
                'points' => $points,
                'balance_after' => $loyaltyPoint->total_points,
                'description' => $description,
            ]);
        });
    }

    private function calculateDiscountForPoints(int $points): float
    {
        // Default: 100 points = ₱50 discount
        return round($points * 0.5, 2);
    }
}
