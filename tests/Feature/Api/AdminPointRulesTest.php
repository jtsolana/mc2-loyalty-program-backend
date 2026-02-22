<?php

use App\Models\PointRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can list point rules', function () {
    makeAdmin();
    PointRule::factory()->count(3)->create();
    $admin = makeAdmin();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/point-rules')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('admin can create a spend-based point rule', function () {
    $admin = makeAdmin();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/point-rules', [
            'name' => 'Standard Rule',
            'type' => 'spend_based',
            'spend_amount' => 50,
            'minimum_spend' => 0,
            'points_per_unit' => 1,
            'is_active' => true,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Standard Rule');

    $this->assertDatabaseHas('point_rules', ['name' => 'Standard Rule', 'type' => 'spend_based']);
});

it('admin can create a per-item point rule', function () {
    $admin = makeAdmin();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/point-rules', [
            'name' => 'Per Drink Rule',
            'type' => 'per_item',
            'points_per_item' => 2,
            'is_active' => true,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Per Drink Rule');

    $this->assertDatabaseHas('point_rules', ['name' => 'Per Drink Rule', 'type' => 'per_item', 'points_per_item' => 2]);
});

it('admin can update a point rule', function () {
    $admin = makeAdmin();
    $rule = PointRule::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/point-rules/{$rule->hashed_id}", [
            'name' => 'Updated Rule',
            'type' => 'spend_based',
            'spend_amount' => 100,
            'minimum_spend' => 50,
            'points_per_unit' => 2,
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Rule');
});

it('admin can delete a point rule', function () {
    $admin = makeAdmin();
    $rule = PointRule::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/point-rules/{$rule->hashed_id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('point_rules', ['id' => $rule->id]);
});

it('customer cannot access admin point rules', function () {
    $customer = User::factory()->create();
    $customer->roles()->attach(Role::where('name', 'customer')->first());

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/admin/point-rules')
        ->assertForbidden();
});

it('staff cannot access admin point rules', function () {
    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->first());

    $this->actingAs($staff, 'sanctum')
        ->getJson('/api/v1/admin/point-rules')
        ->assertForbidden();
});
