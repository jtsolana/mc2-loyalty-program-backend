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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** @deprecated Use calculatePoints() instead */
    public function calculatePointsForAmount(float $amount): int
    {
        return $this->calculatePoints(amount: $amount);
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

            $this->checkAndIssueRewards($customer, $loyaltyPoint->refresh());

            return $transaction;
        });
    }

    /**
     * Check qualifying reward rules and issue rewards for a customer.
     * Points are deducted immediately when a reward is issued.
     */
    public function checkAndIssueRewards(User $customer, ?LoyaltyPoint $loyaltyPoint = null): Collection
    {
        $loyaltyPoint ??= $customer->loyaltyPoint;

        if (! $loyaltyPoint || $loyaltyPoint->total_points === 0) {
            return collect();
        }

        $pendingRuleIds = $customer->rewards()
            ->where('status', RewardStatus::Pending)
            ->pluck('reward_rule_id');

        $qualifyingRules = RewardRule::where('is_active', true)
            ->where('points_required', '<=', $loyaltyPoint->total_points)
            ->whereNotIn('id', $pendingRuleIds)
            ->get();

        $issued = collect();

        foreach ($qualifyingRules as $rule) {
            $reward = $this->issueReward($customer, $rule, $loyaltyPoint);
            $loyaltyPoint->refresh();
            $issued->push($reward);
        }

        return $issued;
    }

    private function issueReward(User $customer, RewardRule $rule, LoyaltyPoint $loyaltyPoint): Reward
    {
        $reward = Reward::create([
            'user_id' => $customer->id,
            'reward_rule_id' => $rule->id,
            'points_deducted' => $rule->points_required,
            'status' => RewardStatus::Pending,
            'expires_at' => Carbon::now()->addDays($rule->expires_in_days),
        ]);

        $loyaltyPoint->decrement('total_points', $rule->points_required);
        $loyaltyPoint->refresh();

        PointTransaction::create([
            'user_id' => $customer->id,
            'type' => TransactionType::Reward->value,
            'points' => -$rule->points_required,
            'balance_after' => $loyaltyPoint->total_points,
            'description' => "Reward issued: {$rule->reward_title}",
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
