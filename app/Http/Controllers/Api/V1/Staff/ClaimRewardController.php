<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LoyverseItemsResource;
use App\Http\Resources\Api\RewardResource;
use App\Jobs\CreateLoyverseRewardReceipt;
use App\Models\RewardRule;
use App\Models\User;
use App\Services\LoyverseService;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ClaimRewardController extends Controller
{
    public function customerRewards(User $user): JsonResponse
    {
        $user->load([
            'loyaltyPoint',
            'rewards' => fn ($q) => $q->whereDate('claimed_at', Carbon::now()->toDateString())->latest(),
        ]);

        $rewardRedemptionLimitPerDay = config('app.reward_redemption_limit_per_day', 1);
        if ($user->rewards->count() >= $rewardRedemptionLimitPerDay) {
            return response()->json([
                'message' => "Only {$rewardRedemptionLimitPerDay} reward(s) can be claimed per day. This customer has already claimed a reward today.",
            ], 422);
        }

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
                'redeemable' => $redeemable,
            ],
        ]);
    }

    public function claim(Request $request, User $user, PointService $pointService): JsonResponse
    {
        $this->validateClaim($request);

        $rewardRedemptionLimitPerDay = config('app.reward_redemption_limit_per_day', 1);
        $rewardsClaimedToday = $user->rewards()->whereDate('claimed_at', Carbon::now()->toDateString())->count();
        if ($rewardsClaimedToday >= $rewardRedemptionLimitPerDay) {
            return response()->json([
                'message' => "Only {$rewardRedemptionLimitPerDay} reward(s) can be claimed per day. This customer has already claimed a reward today.",
            ], 422);
        }

        $claimAmount = $request->input('claim_amount') ?? 1;
        $rewardRule = RewardRule::findOrFail($request->input('reward_rule_id'));
        $loyverseVariantId = $request->input('variant_id');

        if ($rewardRule->is_active !== true) {
            return response()->json([
                'message' => 'Reward cannot be claimed. Current status is not active.',
            ], 422);
        }

        if ($user->loyaltyPoint->total_points < ($rewardRule->points_required * $claimAmount)) {
            return response()->json([
                'message' => 'Insufficient points to claim reward.',
            ], 422);
        }

        $reward = $pointService->claimReward($user, $rewardRule, $user->loyaltyPoint, $claimAmount);

        CreateLoyverseRewardReceipt::dispatch($reward, $user, $loyverseVariantId)
            ->onQueue('loyverse');

        return response()->json([
            'message' => 'Reward successfully claimed.',
            'data' => new RewardResource($reward->load('rewardRule')),
        ]);
    }

    public function getItems(LoyverseService $loyverseService): JsonResponse
    {
        $items = Cache::remember('loyverse_inventory_items', 86400, function () use ($loyverseService) {
            return $loyverseService->getItems();
        });

        return response()->json([
            'data' => LoyverseItemsResource::collection($items),
        ]);
    }

    /** @return array<string, mixed> */
    private function validateClaim(Request $request): array
    {
        return $request->validate([
            'claim_amount' => ['required', 'integer', 'min:1'],
            'reward_rule_id' => ['required', 'integer', 'exists:reward_rules,id'],
            'variant_id' => ['required', 'string'],
        ]);
    }
}
