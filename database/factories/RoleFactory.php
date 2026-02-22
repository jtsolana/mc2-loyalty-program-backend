<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    public function admin(): static
    {
        return $this->state(['name' => 'admin', 'display_name' => 'Administrator']);
    }

    public function staff(): static
    {
        return $this->state(['name' => 'staff', 'display_name' => 'Staff']);
    }

    public function customer(): static
    {
        return $this->state(['name' => 'customer', 'display_name' => 'Customer']);
    }
}
