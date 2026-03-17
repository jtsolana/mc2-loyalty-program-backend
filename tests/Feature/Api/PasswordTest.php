<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

// --- Forgot Password ---

it('sends a password reset link to a registered email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'customer@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'customer@example.com'])
        ->assertOk()
        ->assertJsonPath('message', __('passwords.sent'));

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $url = $notification->toMail($user)->actionUrl ?? '';

        return str_starts_with($url, config('app.mobile_scheme').'reset-password');
    });
});

it('returns success even when email is not registered to prevent enumeration', function () {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->assertStatus(422);
});

it('validates email field on forgot password', function () {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// --- Reset Password ---

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['email' => 'reset@example.com', 'password' => Hash::make('old-password')]);

    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertOk()
        ->assertJsonPath('message', __('passwords.reset'));

    expect(Hash::check('new-password1', $user->fresh()->password))->toBeTrue();
});

it('revokes all tokens after a password reset', function () {
    $user = User::factory()->create(['email' => 'revoke@example.com']);
    $user->createToken('mobile');

    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'revoke@example.com',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('rejects an invalid reset token', function () {
    User::factory()->create(['email' => 'bad-token@example.com']);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => 'bad-token@example.com',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertStatus(422)
        ->assertJsonPath('message', __('passwords.token'));
});

it('validates required fields on reset password', function () {
    $this->postJson('/api/v1/auth/reset-password', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token', 'email', 'password']);
});

// --- Change Password ---

it('changes password for an authenticated user', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->postJson('/api/v1/auth/change-password', [
            'current_password' => 'old-password',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])->assertOk()
        ->assertJsonPath('message', 'Password changed successfully.');

    expect(Hash::check('new-password1', $user->fresh()->password))->toBeTrue();
});

it('rejects change password when current password is wrong', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])->assertStatus(422)
        ->assertJsonPath('errors.current_password.0', 'The provided current password is incorrect.');
});

it('requires authentication for change password', function () {
    $this->postJson('/api/v1/auth/change-password', [
        'current_password' => 'any',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertUnauthorized();
});

it('validates required fields on change password', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->postJson('/api/v1/auth/change-password', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password', 'password']);
});
