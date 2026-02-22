<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class EarnPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
            'amount_spent' => ['nullable', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_spent.numeric' => 'Amount spent must be a valid number.',
            'item_count.integer' => 'Item count must be a whole number.',
            'item_count.min' => 'Item count must be at least 1.',
        ];
    }
}
