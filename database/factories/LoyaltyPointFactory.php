<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoyaltyPoint>
 */
class LoyaltyPointFactory extends Factory
{
    public function definition(): array
    {
        $lifetime = fake()->numberBetween(0, 5000);

        return [
            'user_id' => User::factory(),
            'total_points' => fake()->numberBetween(0, $lifetime),
            'lifetime_points' => $lifetime,
        ];
    }
}
