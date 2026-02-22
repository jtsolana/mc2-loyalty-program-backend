<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'points_required' => $rule->points_required,
            'expires_in_days' => $rule->expires_in_days,
            'is_active' => $rule->is_active,
            'created_at' => $rule->created_at?->toDateString(),
        ]);

        return Inertia::render('admin/reward-rules/index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reward_title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:1'],
            'expires_in_days' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);

        RewardRule::create($request->only(['name', 'reward_title', 'points_required', 'expires_in_days', 'is_active']));

        return back()->with('success', 'Reward rule created successfully.');
    }

    public function update(Request $request, RewardRule $rewardRule): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reward_title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:1'],
            'expires_in_days' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);

        $rewardRule->update($request->only(['name', 'reward_title', 'points_required', 'expires_in_days', 'is_active']));

        return back()->with('success', 'Reward rule updated successfully.');
    }

    public function destroy(RewardRule $rewardRule): RedirectResponse
    {
        $rewardRule->delete();

        return back()->with('success', 'Reward rule deleted.');
    }
}
