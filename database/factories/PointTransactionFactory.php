<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'staff_id' => null,
            'type' => fake()->randomElement(TransactionType::cases())->value,
            'points' => fake()->numberBetween(1, 500),
            'balance_after' => fake()->numberBetween(0, 5000),
            'description' => fake()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
