<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PointRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPerItem = $this->input('type') === 'per_item';

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:spend_based,per_item'],
            'spend_amount' => $isPerItem ? ['nullable'] : ['required', 'numeric', 'min:0.01'],
            'minimum_spend' => $isPerItem ? ['nullable'] : ['required', 'numeric', 'min:0'],
            'points_per_unit' => $isPerItem ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'points_per_item' => $isPerItem ? ['required', 'integer', 'min:1'] : ['nullable'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
