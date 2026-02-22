<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $group = fake()->randomElement(['auth', 'points', 'customers', 'admin']);

        return [
            'name' => $group.'.'.fake()->unique()->word(),
            'display_name' => fake()->words(2, true),
            'group' => $group,
        ];
    }
}
