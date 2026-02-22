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

it('reward is issued automatically when customer reaches point threshold', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    PointRule::factory()->perItem(5)->create(['is_active' => true]);
    $rewardRule = RewardRule::factory()->requiresPoints(10)->create(['expires_in_days' => 30]);

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 2,
        ])
        ->assertStatus(201);

    expect($customer->fresh()->rewards()->count())->toBe(1);

    $reward = $customer->fresh()->rewards()->first();
    expect($reward->status)->toBe(RewardStatus::Pending)
        ->and($reward->points_deducted)->toBe(10)
        ->and($reward->reward_rule_id)->toBe($rewardRule->id);

    expect($customer->fresh()->loyaltyPoint->total_points)->toBe(0);
});

it('reward deducts points from customer balance immediately', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    PointRule::factory()->perItem(100)->create(['is_active' => true]);
    RewardRule::factory()->requiresPoints(50)->create();

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 1,
        ])
        ->assertStatus(201);

    $loyaltyPoint = $customer->fresh()->loyaltyPoint;
    expect($loyaltyPoint->total_points)->toBe(50);
});

it('no duplicate pending reward is issued for the same rule', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    PointRule::factory()->perItem(10)->create(['is_active' => true]);
    RewardRule::factory()->requiresPoints(5)->create();

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 1,
        ])
        ->assertStatus(201);

    expect($customer->fresh()->rewards()->count())->toBe(1);

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 1,
        ])
        ->assertStatus(201);

    expect($customer->fresh()->rewards()->count())->toBe(1);
});

it('inactive reward rule does not trigger reward issuance', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    PointRule::factory()->perItem(10)->create(['is_active' => true]);
    RewardRule::factory()->inactive()->requiresPoints(5)->create();

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 1,
        ])
        ->assertStatus(201);

    expect($customer->fresh()->rewards()->count())->toBe(0);
});

it('customer can view their rewards with rule details', function () {
    $customer = makeCustomerForRewards(0);
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create(['reward_title' => 'Free Coffee']);
    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
    ]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/rewards')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.reward_rule.reward_title', 'Free Coffee')
        ->assertJsonPath('data.0.reward_rule.points_required', 100)
        ->assertJsonPath('data.0.status', 'pending');
});

it('staff can claim a pending reward', function () {
    $customer = makeCustomerForRewards(0);
    $staff = makeStaffForRewards();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();
    $reward = Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->addDays(30),
    ]);

    $this->actingAs($staff, 'sanctum')
        ->postJson("/api/v1/staff/rewards/{$reward->hashed_id}/claim")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'claimed');

    expect($reward->fresh()->status)->toBe(RewardStatus::Claimed)
        ->and($reward->fresh()->claimed_at)->not->toBeNull();
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
