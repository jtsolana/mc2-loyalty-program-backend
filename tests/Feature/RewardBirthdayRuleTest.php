<?php

use App\Enums\RewardRuleType;
use App\Models\RewardRule;
use App\Models\User;

test('can create a birthday reward rule with zero points required and 1 day expiration', function () {
    $rule = RewardRule::factory()->birthday()->create();

    expect($rule)->toBeInstanceOf(RewardRule::class)
        ->and($rule->type)->toBe(RewardRuleType::Birthday)
        ->and($rule->points_required)->toBe(0)
        ->and($rule->expires_in_days)->toBe(1)
        ->and($rule->is_active)->toBeTrue();
});

test('can create a points-based reward rule', function () {
    $rule = RewardRule::factory()->create([
        'type' => 'points_based',
        'points_required' => 500,
        'expires_in_days' => 30,
    ]);

    expect($rule)->toBeInstanceOf(RewardRule::class)
        ->and($rule->type)->toBe(RewardRuleType::PointsBased)
        ->and($rule->points_required)->toBe(500)
        ->and($rule->expires_in_days)->toBe(30);
});

test('birthday rule is only applicable when customer birthday is today', function () {
    $rule = RewardRule::factory()->birthday()->create();

    // Customer with today's birthday
    $customerWithBirthday = User::factory()->create([
        'date_of_birth' => today(),
    ]);

    // Customer with different birthday
    $customerWithoutBirthday = User::factory()->create([
        'date_of_birth' => today()->subDays(1),
    ]);

    expect($rule->isApplicableToUser($customerWithBirthday))->toBeTrue()
        ->and($rule->isApplicableToUser($customerWithoutBirthday))->toBeFalse();
});

test('points-based rule is only applicable when customer has enough points', function () {
    $rule = RewardRule::factory()->create([
        'type' => 'points_based',
        'points_required' => 500,
    ]);

    // Customer with enough points
    $richCustomer = User::factory()->create();
    $richCustomer->loyaltyPoint()->create(['total_points' => 600, 'lifetime_points' => 600]);

    // Customer without enough points
    $poorCustomer = User::factory()->create();
    $poorCustomer->loyaltyPoint()->create(['total_points' => 300, 'lifetime_points' => 300]);

    expect($rule->isApplicableToUser($richCustomer))->toBeTrue()
        ->and($rule->isApplicableToUser($poorCustomer))->toBeFalse();
});

test('birthday rule has zero points required', function () {
    $rule = RewardRule::factory()->birthday()->create();

    expect($rule->points_required)->toBe(0);
});

test('birthday rule expires in exactly 1 day', function () {
    $rule = RewardRule::factory()->birthday()->create();

    expect($rule->expires_in_days)->toBe(1);
});

test('customer with no date of birth is not applicable for birthday rule', function () {
    $rule = RewardRule::factory()->birthday()->create();

    $customerNoDateOfBirth = User::factory()->create([
        'date_of_birth' => null,
    ]);

    expect($rule->isApplicableToUser($customerNoDateOfBirth))->toBeFalse();
});

test('can update birthday rule', function () {
    $rule = RewardRule::factory()->birthday()->create();

    $rule->update([
        'name' => 'Updated Birthday Rule',
        'reward_title' => 'Updated Free Item',
        'is_active' => false,
    ]);

    expect($rule->name)->toBe('Updated Birthday Rule')
        ->and($rule->reward_title)->toBe('Updated Free Item')
        ->and($rule->type)->toBe(RewardRuleType::Birthday)
        ->and($rule->is_active)->toBeFalse();
});

test('birthday rule factory creates correct defaults', function () {
    $rule = RewardRule::factory()->birthday()->create();

    expect($rule->type->value)->toBe('birthday')
        ->and($rule->points_required)->toBe(0)
        ->and($rule->expires_in_days)->toBe(1)
        ->and($rule->is_active)->toBeTrue();
});

test('points-based rule factory creates correct defaults', function () {
    $rule = RewardRule::factory()->create([
        'type' => 'points_based',
    ]);

    expect($rule->type->value)->toBe('points_based')
        ->and($rule->points_required)->toBeGreaterThan(0)
        ->and($rule->expires_in_days)->toBe(30)
        ->and($rule->is_active)->toBeTrue();
});

test('reward rule type is cast to enum', function () {
    $rule = RewardRule::factory()->birthday()->create();

    expect($rule->type)->toBeInstanceOf(RewardRuleType::class)
        ->and($rule->type)->toBe(RewardRuleType::Birthday);
});

test('customer with birthday tomorrow is not applicable for birthday rule', function () {
    $rule = RewardRule::factory()->birthday()->create();

    $customer = User::factory()->create([
        'date_of_birth' => today()->addDay(),
    ]);

    expect($rule->isApplicableToUser($customer))->toBeFalse();
});

test('points-based rule uses checkPointsRequired method', function () {
    $rule = RewardRule::factory()->create([
        'type' => 'points_based',
        'points_required' => 1000,
    ]);

    $customer = User::factory()->create();
    $customer->loyaltyPoint()->create(['total_points' => 1000, 'lifetime_points' => 1000]);

    // Exactly at the threshold
    expect($rule->isApplicableToUser($customer))->toBeTrue();

    // Just below threshold
    $customer->loyaltyPoint()->update(['total_points' => 999]);
    $customer->refresh();
    expect($rule->isApplicableToUser($customer))->toBeFalse();
});
