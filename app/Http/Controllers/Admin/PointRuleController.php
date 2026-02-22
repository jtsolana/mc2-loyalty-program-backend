<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PointRuleType;
use App\Http\Controllers\Controller;
use App\Models\PointRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointRuleController extends Controller
{
    public function index(): Response
    {
        $rules = PointRule::latest()->get()->map(fn ($rule) => [
            'id' => $rule->id,
            'hashed_id' => $rule->hashed_id,
            'name' => $rule->name,
            'type' => $rule->type->value,
            'spend_amount' => $rule->spend_amount,
            'minimum_spend' => $rule->minimum_spend,
            'points_per_unit' => $rule->points_per_unit,
            'points_per_item' => $rule->points_per_item,
            'is_active' => $rule->is_active,
            'created_at' => $rule->created_at?->toDateString(),
        ]);

        return Inertia::render('admin/point-rules/index', [
            'rules' => $rules,
            'ruleTypes' => collect(PointRuleType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRuleRequest($request);
        PointRule::create($data);

        return back()->with('success', 'Point rule created successfully.');
    }

    public function update(Request $request, PointRule $pointRule): RedirectResponse
    {
        $data = $this->validateRuleRequest($request);
        $pointRule->update($data);

        return back()->with('success', 'Point rule updated successfully.');
    }

    public function destroy(PointRule $pointRule): RedirectResponse
    {
        $pointRule->delete();

        return back()->with('success', 'Point rule deleted.');
    }

    /** @return array<string, mixed> */
    private function validateRuleRequest(Request $request): array
    {
        $isPerItem = $request->input('type') === PointRuleType::PerItem->value;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:spend_based,per_item'],
            'spend_amount' => $isPerItem ? ['nullable'] : ['required', 'numeric', 'min:0.01'],
            'minimum_spend' => $isPerItem ? ['nullable'] : ['required', 'numeric', 'min:0'],
            'points_per_unit' => $isPerItem ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'points_per_item' => $isPerItem ? ['required', 'integer', 'min:1'] : ['nullable'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
