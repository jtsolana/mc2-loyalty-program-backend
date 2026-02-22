<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can list all roles', function () {
    $this->actingAs(adminUser(), 'sanctum')
        ->getJson('/api/v1/admin/roles')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name', 'display_name', 'permissions']]]);
});

it('admin can create a new role', function () {
    $this->actingAs(adminUser(), 'sanctum')
        ->postJson('/api/v1/admin/roles', [
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'A custom manager role',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'manager');

    $this->assertDatabaseHas('roles', ['name' => 'manager']);
});

it('admin can view a role with its permissions', function () {
    $role = Role::where('name', 'staff')->first();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson("/api/v1/admin/roles/{$role->hashed_id}")
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'staff')
        ->assertJsonStructure(['data' => ['permissions']]);
});

it('admin can sync permissions to a role', function () {
    $admin = adminUser();
    $role = Role::factory()->create();
    $permission = Permission::where('name', 'points.view')->first();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/roles/{$role->hashed_id}/permissions", [
            'permissions' => [$permission->id],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.permissions.0.name', 'points.view');

    expect($role->fresh()->permissions()->where('name', 'points.view')->exists())->toBeTrue();
});

it('admin can delete a custom role', function () {
    $admin = adminUser();
    $role = Role::factory()->create(['name' => 'custom-role']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/roles/{$role->hashed_id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('cannot delete a built-in role', function () {
    $admin = adminUser();
    $adminRole = Role::where('name', 'admin')->first();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/roles/{$adminRole->hashed_id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('roles', ['name' => 'admin']);
});

it('admin can view available and assigned permissions for a role', function () {
    $role = Role::where('name', 'staff')->first();

    $this->actingAs(adminUser(), 'sanctum')
        ->getJson("/api/v1/admin/roles/{$role->hashed_id}/permissions")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['role', 'assigned', 'available']]);
});

it('permission enforcement prevents staff from managing roles', function () {
    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->first());

    $this->actingAs($staff, 'sanctum')
        ->getJson('/api/v1/admin/roles')
        ->assertForbidden();
});
