<?php

use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeAdminForDashboard(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can view the dashboard', function () {
    $admin = makeAdminForDashboard();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/dashboard')
                ->has('stats')
                ->has('recentCustomers')
                ->has('monthlyPurchases')
                ->has('filters')
                ->where('filters.start_date', now()->toDateString())
                ->where('filters.end_date', now()->toDateString()),
        );
});

it('dashboard filters data by date range', function () {
    $admin = makeAdminForDashboard();

    Purchase::factory()->create(['created_at' => now()->subDays(5)]);
    Purchase::factory()->create(['created_at' => now()->subDays(1)]);
    Purchase::factory()->create(['created_at' => now()]);

    $this->actingAs($admin)
        ->get('/admin?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('stats.total_purchases', 1)
                ->where('filters.start_date', now()->toDateString())
                ->where('filters.end_date', now()->toDateString()),
        );
});

it('dashboard returns correct filters prop when date range is supplied', function () {
    $admin = makeAdminForDashboard();

    $start = now()->subDays(7)->toDateString();
    $end = now()->toDateString();

    $this->actingAs($admin)
        ->get("/admin?start_date={$start}&end_date={$end}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.start_date', $start)
                ->where('filters.end_date', $end),
        );
});
