<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreRewardRuleRequest;
use App\Http\Requests\Api\Admin\UpdateRewardRuleRequest;
use App\Models\RewardRule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RewardRuleController extends Controller
{
    public function index(): Response
    {
        $rules = RewardRule::latest()->get()->map(fn ($rule) => [
            'id' => $rule->id,
            'hashed_id' => $rule->hashed_id,
            'name' => $rule->name,
            'reward_title' => $rule->reward_title,
            'type' => $rule->type->value,
            'points_required' => $rule->points_required,
            'expires_in_days' => $rule->expires_in_days,
            'is_active' => $rule->is_active,
            'created_at' => $rule->created_at?->toDateString(),
        ]);

        return Inertia::render('admin/reward-rules/index', [
            'rules' => $rules,
        ]);
    }

    public function store(StoreRewardRuleRequest $request): RedirectResponse
    {
        RewardRule::create($request->validated());

        return back()->with('success', 'Reward rule created successfully.');
    }

    public function update(UpdateRewardRuleRequest $request, RewardRule $rewardRule): RedirectResponse
    {
        $rewardRule->update($request->validated());

        return back()->with('success', 'Reward rule updated successfully.');
    }

    public function destroy(RewardRule $rewardRule): RedirectResponse
    {
        $rewardRule->delete();

        return back()->with('success', 'Reward rule deleted.');
    }
}
