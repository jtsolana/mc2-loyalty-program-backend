<?php

use App\Models\RewardRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeAdminForRewards(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can list reward rules', function () {
    $admin = makeAdminForRewards();
    RewardRule::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/reward-rules')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('admin can create a reward rule', function () {
    $admin = makeAdminForRewards();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/reward-rules', [
            'name' => 'Free Drink',
            'reward_title' => '1 Free Regular Drink',
            'points_required' => 500,
            'expires_in_days' => 30,
            'is_active' => true,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Free Drink')
        ->assertJsonPath('data.reward_title', '1 Free Regular Drink')
        ->assertJsonPath('data.points_required', 500);

    $this->assertDatabaseHas('reward_rules', ['name' => 'Free Drink', 'points_required' => 500]);
});

it('admin can update a reward rule', function () {
    $admin = makeAdminForRewards();
    $rule = RewardRule::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/reward-rules/{$rule->hashed_id}", [
            'name' => 'Updated Reward',
            'reward_title' => '1 Free Large Drink',
            'points_required' => 750,
            'expires_in_days' => 60,
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Reward')
        ->assertJsonPath('data.points_required', 750);
});

it('admin can delete a reward rule', function () {
    $admin = makeAdminForRewards();
    $rule = RewardRule::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/reward-rules/{$rule->hashed_id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('reward_rules', ['id' => $rule->id]);
});

it('creation fails with missing required fields', function () {
    $admin = makeAdminForRewards();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/reward-rules', [
            'name' => 'Incomplete Rule',
        ])
        ->assertStatus(422);
});

it('customer cannot access admin reward rules', function () {
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::where('name', 'customer')->first());

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/admin/reward-rules')
        ->assertForbidden();
});

it('staff cannot access admin reward rules', function () {
    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->first());

    $this->actingAs($staff, 'sanctum')
        ->getJson('/api/v1/admin/reward-rules')
        ->assertForbidden();
});
