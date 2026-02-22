<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['google', 'facebook']),
            'provider_id' => fake()->unique()->numerify('##########'),
            'provider_token' => fake()->sha256(),
            'provider_refresh_token' => fake()->sha256(),
        ];
    }
}
