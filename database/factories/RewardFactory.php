<?php

namespace Database\Factories;

use App\Enums\RewardStatus;
use App\Models\RewardRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reward_rule_id' => RewardRule::factory(),
            'staff_id' => null,
            'points_deducted' => 500,
            'status' => RewardStatus::Pending,
            'expires_at' => Carbon::now()->addDays(30),
            'claimed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => RewardStatus::Pending,
            'claimed_at' => null,
        ]);
    }

    public function claimed(): static
    {
        return $this->state([
            'status' => RewardStatus::Claimed,
            'claimed_at' => Carbon::now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => RewardStatus::Expired,
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }
}
