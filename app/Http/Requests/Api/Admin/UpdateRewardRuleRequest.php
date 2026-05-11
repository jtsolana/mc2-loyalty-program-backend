<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type', 'points_based');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'reward_title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in('points_based', 'birthday')],
            'is_active' => ['required', 'boolean'],
        ];

        if ($type === 'points_based') {
            $rules['points_required'] = ['required', 'integer', 'min:1'];
            $rules['expires_in_days'] = ['required', 'integer', 'min:1'];
        } elseif ($type === 'birthday') {
            $rules['expires_in_days'] = ['required', 'integer', 'in:1'];
        }

        return $rules;
    }

    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        // For birthday rules, set points_required to 0
        if (isset($validated['type']) && $validated['type'] === 'birthday') {
            $validated['points_required'] = 0;
        }

        return $validated;
    }
}
