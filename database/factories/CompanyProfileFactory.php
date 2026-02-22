<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo' => null,
            'address' => fake()->address(),
            'contact_number' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }
}
