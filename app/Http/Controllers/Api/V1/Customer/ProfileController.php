<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'roles.permissions',
            'loyaltyPoint',
            'rewards' => fn ($q) => $q->where('status', RewardStatus::Pending)->with('rewardRule'),
        ]);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'data' => new UserResource($request->user()->fresh()->load('roles.permissions', 'loyaltyPoint')),
        ]);
    }
}
