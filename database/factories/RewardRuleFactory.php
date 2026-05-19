<?php

namespace Database\Factories;

use App\Models\RewardRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RewardRule>
 */
class RewardRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'reward_title' => '1 Free '.fake()->word(),
            'type' => 'points_based',
            'points_required' => fake()->numberBetween(100, 1000),
            'expires_in_days' => 30,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function birthday(): static
    {
        return $this->state([
            'type' => 'birthday',
            'points_required' => 0,
            'expires_in_days' => 1,
        ]);
    }

    public function requiresPoints(int $points): static
    {
        return $this->state(['points_required' => $points]);
    }
}
