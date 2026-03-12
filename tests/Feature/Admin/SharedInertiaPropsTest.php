<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shares can.roles.manage as true for a user with that permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    $this->actingAs($user)
        ->get('/admin/company-profile')
        ->assertInertia(
            fn ($page) => $page->where('auth.can.roles_manage', true),
        );
});

it('shares can.roles.manage as false for a user without that permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->first());

    $this->actingAs($user)
        ->get('/admin/customers')
        ->assertInertia(
            fn ($page) => $page->where('auth.can.roles_manage', false),
        );
});
