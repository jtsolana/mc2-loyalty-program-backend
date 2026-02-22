<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'excerpt' => fake()->sentence(12),
            'thumbnail' => null,
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'type' => fake()->randomElement(['promotion', 'announcement']),
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function promotion(): static
    {
        return $this->state(['type' => 'promotion']);
    }

    public function announcement(): static
    {
        return $this->state(['type' => 'announcement']);
    }
}
