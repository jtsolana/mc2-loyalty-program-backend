<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RewardResource;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\RewardRule;
use App\Services\PointService;
use App\Jobs\SendPushNotificationToCustomers;

class ClaimRewardController extends Controller
{
    public function customerRewards(User $user): JsonResponse
    {
        $user->load([
            'loyaltyPoint',
            'rewards' => fn ($q) => $q->whereIn('status', [RewardStatus::Claimed->value, RewardStatus::Expired->value])
                ->with('rewardRule')
                ->latest()
        ]);

        $totalPoints = $user->loyaltyPoint?->total_points ?? 0;

        $redeemable = RewardRule::query()
            ->where('is_active', true)
            ->where('points_required', '<=', $totalPoints)
            ->get()
            ->map(fn (RewardRule $rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'reward_title' => $rule->reward_title,
                'points_required' => $rule->points_required,
                'redeemable_count' => (int) floor($totalPoints / $rule->points_required),
            ]);

        return response()->json([
            'data' => [
                'current_points' => $totalPoints,
                'redeemable' => $redeemable
            ],
        ]);
    }

    public function claim(Request $request, User $user): JsonResponse
    {
        $this->validateClaim($request);

        $claimAmount = $request->input('claim_amount');
        $rewardRule = RewardRule::findOrFail($request->input('reward_rule_id'));

        if ($rewardRule->is_active !== true) {
            return response()->json([
                'message' => "Reward cannot be claimed. Current status is not active.",
            ], 422);
        }
        
        if($user->loyaltyPoint->total_points < ($rewardRule->points_required * $claimAmount)) {
            return response()->json([
                'message' => "Insufficient points to claim reward.",
            ], 422);
        }
        
        $pointService = new PointService();
        $reward = $pointService->claimReward($user, $rewardRule, $user->loyaltyPoint, $claimAmount);

        $reward->update([
            'status' => RewardStatus::Claimed,
            'staff_id' => $request->user()->id,
            'claimed_at' => Carbon::now(),
        ]);

        $mobileScheme = config('app.mobile_scheme');

        SendPushNotificationToCustomers::dispatch(
            "🎉 Reward Claimed!",
            "You have successfully claimed: {$rewardRule->reward_title} ({$claimAmount}x)",
            [
                'type' => 'reward',
                'user_id' => (string) $user->hashed_id,
                'deep_link' => "{$mobileScheme}rewards",
            ],
            $user->id
        )->onQueue('loyverse');

        return response()->json([
            'message' => 'Reward successfully claimed.',
            'data' => new RewardResource($reward->load('rewardRule')),
        ]);
    }

    /** @return array<string, mixed> */
    private function validateClaim(Request $request): array
    {
        return $request->validate([
            'claim_amount' => ['required', 'integer', 'min:1'],
            'reward_rule_id' => ['required', 'integer', 'exists:reward_rules,id'],
        ]);
    }
}
