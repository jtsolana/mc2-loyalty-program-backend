<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

it('verifies email via signed link without requiring authentication', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify.mobile',
        Carbon::now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($url)->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('renders the email verified inertia page on success', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify.mobile',
        Carbon::now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($url)->assertInertia(fn ($page) => $page->component('auth/email-verified'));
});

it('does not fail when email is already verified', function () {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify.mobile',
        Carbon::now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($url)->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify.mobile',
        Carbon::now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify.mobile',
        Carbon::now()->subMinute(),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
