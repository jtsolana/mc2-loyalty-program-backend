<?php

use App\Enums\RewardStatus;
use App\Models\LoyaltyPoint;
use App\Models\PointRule;
use App\Models\Reward;
use App\Models\RewardRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeCustomerForRewards(int $totalPoints = 0): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'customer')->first());
    LoyaltyPoint::factory()->create(['user_id' => $user->id, 'total_points' => $totalPoints, 'lifetime_points' => $totalPoints]);

    return $user;
}

function makeStaffForRewards(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->first());

    return $user;
}

it('customer can view redeemable rewards based on current points', function () {
    $customer = makeCustomerForRewards(30);
    RewardRule::factory()->requiresPoints(10)->create(['reward_title' => 'Free Drink']);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/rewards')
        ->assertSuccessful()
        ->assertJsonPath('data.current_points', 30)
        ->assertJsonCount(1, 'data.redeemable')
        ->assertJsonPath('data.redeemable.0.reward_title', 'Free Drink')
        ->assertJsonPath('data.redeemable.0.points_required', 10)
        ->assertJsonPath('data.redeemable.0.redeemable_count', 3);
});

it('customer sees no redeemable rewards when points are insufficient', function () {
    $customer = makeCustomerForRewards(5);
    RewardRule::factory()->requiresPoints(10)->create(['reward_title' => 'Free Drink']);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/rewards')
        ->assertSuccessful()
        ->assertJsonPath('data.current_points', 5)
        ->assertJsonCount(0, 'data.redeemable');
});

it('customer reward response includes current points and empty history when no rewards claimed', function () {
    $customer = makeCustomerForRewards(50);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/rewards')
        ->assertSuccessful()
        ->assertJsonPath('data.current_points', 50)
        ->assertJsonStructure(['data' => ['current_points', 'redeemable', 'history']])
        ->assertJsonCount(0, 'data.history');
});

it('staff cannot claim an already claimed reward', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();
    $reward = Reward::factory()->claimed()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->postJson("/api/v1/staff/rewards/{$reward->hashed_id}/claim")
        ->assertStatus(422);
});

it('staff cannot claim an expired reward', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();
    $reward = Reward::factory()->expired()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->postJson("/api/v1/staff/rewards/{$reward->hashed_id}/claim")
        ->assertStatus(422);
});

it('expire command marks past-due pending rewards as expired', function () {
    $customer = makeCustomerForRewards(0);
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();

    $expiredReward = Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->subDay(),
    ]);

    $activeReward = Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->addDays(10),
    ]);

    $this->artisan('rewards:expire')->assertExitCode(0);

    expect($expiredReward->fresh()->status)->toBe(RewardStatus::Expired)
        ->and($activeReward->fresh()->status)->toBe(RewardStatus::Pending);
});
