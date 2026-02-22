<?php

namespace Database\Factories;

use App\Enums\PointRuleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointRule>
 */
class PointRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => PointRuleType::SpendBased,
            'spend_amount' => 50.00,
            'minimum_spend' => 0.00,
            'points_per_unit' => 1,
            'points_per_item' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function perItem(int $pointsPerItem = 1): static
    {
        return $this->state([
            'type' => PointRuleType::PerItem,
            'points_per_item' => $pointsPerItem,
            'spend_amount' => null,
            'minimum_spend' => null,
            'points_per_unit' => null,
        ]);
    }
}
