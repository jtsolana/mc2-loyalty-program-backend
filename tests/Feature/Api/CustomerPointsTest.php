<?php

use App\Enums\RewardStatus;
use App\Models\LoyaltyPoint;
use App\Models\PointTransaction;
use App\Models\Purchase;
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

it('point history includes purchase_items when reference is a purchase', function () {
    $customer = makeCustomer();

    $lineItems = [
        ['item_id' => 'abc', 'name' => 'Coffee', 'quantity' => 1, 'total_money' => 150.00],
        ['item_id' => 'def', 'name' => 'Cake', 'quantity' => 2, 'total_money' => 200.00],
    ];

    $purchase = Purchase::factory()->create([
        'user_id' => $customer->id,
        'loyverse_payload' => ['receipt_number' => 'R-001', 'line_items' => $lineItems],
    ]);

    PointTransaction::factory()->create([
        'user_id' => $customer->id,
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/points/history')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.0.purchase_items')
        ->assertJsonPath('data.0.purchase_items.0.name', 'Coffee')
        ->assertJsonPath('data.0.purchase_items.1.name', 'Cake');
});

it('point history returns empty purchase_items when reference is not a purchase', function () {
    $customer = makeCustomer();

    PointTransaction::factory()->create([
        'user_id' => $customer->id,
        'reference_type' => null,
        'reference_id' => null,
    ]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/points/history')
        ->assertSuccessful()
        ->assertJsonPath('data.0.purchase_items', []);
});

it('customer can view their profile', function () {
    $customer = makeCustomer();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJsonPath('data.email', $customer->email);
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
