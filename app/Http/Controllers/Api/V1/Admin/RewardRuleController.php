<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreRewardRuleRequest;
use App\Http\Requests\Api\Admin\UpdateRewardRuleRequest;
use App\Http\Resources\Api\RewardRuleResource;
use App\Models\RewardRule;
use Illuminate\Http\JsonResponse;

class RewardRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => RewardRuleResource::collection(RewardRule::latest()->get())]);
    }

    public function store(StoreRewardRuleRequest $request): JsonResponse
    {
        $rule = RewardRule::create($request->validated());

        return response()->json(['data' => new RewardRuleResource($rule)], 201);
    }

    public function show(RewardRule $rewardRule): JsonResponse
    {
        return response()->json(['data' => new RewardRuleResource($rewardRule)]);
    }

    public function update(UpdateRewardRuleRequest $request, RewardRule $rewardRule): JsonResponse
    {
        $rewardRule->update($request->validated());

        return response()->json(['data' => new RewardRuleResource($rewardRule)]);
    }

    public function destroy(RewardRule $rewardRule): JsonResponse
    {
        $rewardRule->delete();

        return response()->json(['message' => 'Reward rule deleted.']);
    }
}
