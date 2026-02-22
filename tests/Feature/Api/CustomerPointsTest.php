<?php

use App\Enums\RewardStatus;
use App\Models\LoyaltyPoint;
use App\Models\PointRule;
use App\Models\Reward;
use App\Models\RewardRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeCustomer(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'customer')->first());
    LoyaltyPoint::factory()->create(['user_id' => $user->id, 'total_points' => 200, 'lifetime_points' => 500]);

    return $user;
}

function makeStaff(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->first());

    return $user;
}

it('customer can view their point balance', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/points')
        ->assertSuccessful()
        ->assertJsonPath('data.total_points', 200)
        ->assertJsonPath('data.lifetime_points', 500);
});

it('customer can view point transaction history', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/points/history')
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'meta']);
});

it('customer can view their profile', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJsonPath('data.email', $customer->email);
});

it('profile includes pending rewards with rule details', function () {
    $customer = makeCustomer();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create(['reward_title' => 'Free Coffee']);
    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
    ]);

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful();

    $response->assertJsonCount(1, 'data.rewards')
        ->assertJsonPath('data.rewards.0.status', 'pending')
        ->assertJsonPath('data.rewards.0.reward_rule.reward_title', 'Free Coffee');
});

it('profile does not include claimed or expired rewards', function () {
    $customer = makeCustomer();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();
    Reward::factory()->claimed()->create(['user_id' => $customer->id, 'reward_rule_id' => $rewardRule->id, 'points_deducted' => 100]);
    Reward::factory()->expired()->create(['user_id' => $customer->id, 'reward_rule_id' => $rewardRule->id, 'points_deducted' => 100]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.rewards');
});

it('profile includes reward progress for active rules without a pending reward', function () {
    $customer = makeCustomer();
    RewardRule::factory()->requiresPoints(500)->create(['name' => 'Big Reward', 'reward_title' => '1 Free Meal']);

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful();

    $response->assertJsonCount(1, 'data.reward_progress')
        ->assertJsonPath('data.reward_progress.0.name', 'Big Reward')
        ->assertJsonPath('data.reward_progress.0.points_required', 500)
        ->assertJsonPath('data.reward_progress.0.current_points', 200)
        ->assertJsonPath('data.reward_progress.0.points_remaining', 300)
        ->assertJsonPath('data.reward_progress.0.progress_percentage', 40);
});

it('profile excludes reward rule from progress when customer already has a pending reward for it', function () {
    $customer = makeCustomer();
    $rewardRule = RewardRule::factory()->requiresPoints(100)->create();
    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'points_deducted' => 100,
        'status' => RewardStatus::Pending,
    ]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.reward_progress');
});

it('customer can update their profile', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->putJson('/api/v1/customer/profile', ['name' => 'Updated Name'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('unauthenticated user cannot view points', function () {
    $this->getJson('/api/v1/customer/points')->assertStatus(401);
});

it('staff can earn points for a customer using spend-based rule', function () {
    $customer = makeCustomer();
    $staff = makeStaff();
    PointRule::factory()->create(['spend_amount' => 50, 'points_per_unit' => 1, 'is_active' => true]);

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'amount_spent' => 150,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.points', 3);

    expect($customer->fresh()->loyaltyPoint->total_points)->toBe(203);
});

it('staff can earn points for a customer using per-item rule', function () {
    $customer = makeCustomer();
    $staff = makeStaff();
    PointRule::factory()->perItem(2)->create(['is_active' => true]);

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'item_count' => 3,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.points', 6);

    expect($customer->fresh()->loyaltyPoint->total_points)->toBe(206);
});

it('staff can redeem points for a customer', function () {
    $customer = makeCustomer();
    $staff = makeStaff();

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/redeem-points', [
            'user_id' => $customer->id,
            'points_to_redeem' => 100,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.points_used', 100);

    expect($customer->fresh()->loyaltyPoint->total_points)->toBe(100);
});

it('redemption fails when customer has insufficient points', function () {
    $customer = makeCustomer();
    $staff = makeStaff();

    $this->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/staff/redeem-points', [
            'user_id' => $customer->id,
            'points_to_redeem' => 9999,
        ])
        ->assertStatus(422);
});

it('customer cannot earn points', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/staff/earn-points', [
            'user_id' => $customer->id,
            'amount_spent' => 100,
        ])
        ->assertForbidden();
});
