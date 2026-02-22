<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points_to_redeem' => ['required', 'integer', 'min:1'],
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
        ];
    }
}
