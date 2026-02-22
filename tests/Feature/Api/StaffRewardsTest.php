<?php

use App\Enums\RewardStatus;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\RewardRule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;

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

it('staff can list a customers pending rewards via hashed id', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints();
    $rewardRule = RewardRule::factory()->create(['reward_title' => 'Free Drink']);

    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->addDays(30),
    ]);

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.reward_rule.reward_title', 'Free Drink')
        ->assertJsonPath('data.0.status', 'pending');
});

it('returns an empty list when the customer has no pending rewards', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints();

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('only returns pending rewards not claimed or expired ones', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints();
    $rewardRule = RewardRule::factory()->create();

    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->addDays(30),
    ]);

    Reward::factory()->claimed()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
    ]);

    Reward::factory()->expired()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending');
});

it('response includes reward rule details', function () {
    $staff = makeStaffMember();
    $customer = makeCustomerWithPoints();
    $rewardRule = RewardRule::factory()->create([
        'name' => 'Loyalty Tier',
        'reward_title' => '1 Free Regular Drink',
        'points_required' => 500,
    ]);

    Reward::factory()->create([
        'user_id' => $customer->id,
        'reward_rule_id' => $rewardRule->id,
        'status' => RewardStatus::Pending,
        'expires_at' => Carbon::now()->addDays(30),
        'points_deducted' => 500,
    ]);

    $this->actingAs($staff, 'sanctum')
        ->getJson("/api/v1/staff/customers/{$customer->hashed_id}/rewards")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                ['id', 'status', 'points_deducted', 'expires_at', 'reward_rule' => ['name', 'reward_title', 'points_required']],
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
