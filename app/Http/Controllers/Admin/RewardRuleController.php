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
        return Inertia::render('admin/reward-rules/index', [
            'rules' => RewardRule::latest()->get(),
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
