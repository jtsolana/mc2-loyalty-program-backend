<?php

use App\Models\LoyaltyPoint;
use App\Models\RewardRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeStaffMember(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->first());

    return $user;
}

function makeCustomerWithPoints(int $points = 0): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'customer')->first());
    LoyaltyPoint::factory()->create(['user_id' => $user->id, 'total_points' => $points, 'lifetime_points' => $points]);

    return $user;
}

it('staff can list a customers redeemable rewards via hashed id based on their available points', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints(100);
    RewardRule::factory()->create([
        'name' => 'Loyalty Tier',
        'reward_title' => '1 Free Regular Drink',
        'points_required' => 50,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.redeemable')
        ->assertJsonPath('data.redeemable.0.reward_title', '1 Free Regular Drink');
});

it('returns an empty list when the customer has no enough points for any rewards', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints();

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.redeemable');
});


it('response includes rewards details', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints(1000);

    RewardRule::factory()->create([
        'name' => 'Loyalty Tier',
        'reward_title' => '1 Free Regular Drink',
        'points_required' => 500,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'current_points', 
                'redeemable' => [
                    ['id', 'name', 'reward_title', 'points_required', 'redeemable_count']
                ],
            ],
        ]);
});

it('customer cannot access the staff customer rewards endpoint', function () {
    $customer = makeCustomerWithPoints();
    $otherCustomer = makeCustomerWithPoints();

    $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$otherCustomer->hashed_id}/rewards")
        ->assertForbidden();
});

it('unauthenticated request is rejected', function () {
    $customer = makeCustomerWithPoints();

    $this->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertUnauthorized();
});
