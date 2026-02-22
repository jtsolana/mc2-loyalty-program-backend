<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RewardRule>
 */
class RewardRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'reward_title' => '1 Free '.fake()->word(),
            'points_required' => fake()->numberBetween(100, 1000),
            'expires_in_days' => 30,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function requiresPoints(int $points): static
    {
        return $this->state(['points_required' => $points]);
    }
}
