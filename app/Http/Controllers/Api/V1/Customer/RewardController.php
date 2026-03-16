<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RewardResource;
use App\Models\RewardRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load([
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
                'id' => $rule->hashed_id,
                'name' => $rule->name,
                'reward_title' => $rule->reward_title,
                'points_required' => $rule->points_required,
                'redeemable_count' => (int) floor($totalPoints / $rule->points_required),
            ]);

        return response()->json([
            'data' => [
                'current_points' => $totalPoints,
                'redeemable' => $redeemable,
                'todays_reward_limit_reached' => $user->todaysRewardLimitReached,
                'history' => RewardResource::collection($user->rewards),
            ],
        ]);
    }
}
