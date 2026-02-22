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

class ClaimRewardController extends Controller
{
    public function customerRewards(User $user): JsonResponse
    {
        $rewards = $user->rewards()
            ->where('status', RewardStatus::Pending)
            ->with('rewardRule')
            ->latest()
            ->get();

        return response()->json([
            'data' => RewardResource::collection($rewards),
        ]);
    }

    public function claim(Request $request, Reward $reward): JsonResponse
    {
        if ($reward->status !== RewardStatus::Pending) {
            return response()->json([
                'message' => "Reward cannot be claimed. Current status: {$reward->status->value}.",
            ], 422);
        }

        if ($reward->expires_at->isPast()) {
            $reward->update(['status' => RewardStatus::Expired]);

            return response()->json(['message' => 'Reward has expired.'], 422);
        }

        $reward->update([
            'status' => RewardStatus::Claimed,
            'staff_id' => $request->user()->id,
            'claimed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Reward successfully claimed.',
            'data' => new RewardResource($reward->load('rewardRule')),
        ]);
    }
}
