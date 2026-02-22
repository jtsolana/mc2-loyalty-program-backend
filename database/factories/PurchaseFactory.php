<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'loyverse_receipt_id' => Str::uuid()->toString(),
            'loyverse_customer_id' => null,
            'total_amount' => fake()->randomFloat(2, 50, 5000),
            'points_earned' => fake()->numberBetween(0, 100),
            'status' => PurchaseStatus::Completed->value,
            'loyverse_payload' => ['receipt_number' => fake()->numerify('R-####')],
        ];
    }
}
