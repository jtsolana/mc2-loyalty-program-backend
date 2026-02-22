<?php

namespace Database\Factories;

use App\Enums\RedemptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Redemption>
 */
class RedemptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'staff_id' => User::factory(),
            'purchase_id' => null,
            'points_used' => fake()->numberBetween(10, 500),
            'discount_amount' => fake()->randomFloat(2, 5, 250),
            'status' => RedemptionStatus::Applied->value,
        ];
    }
}
