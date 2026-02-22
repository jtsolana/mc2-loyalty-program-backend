<?php

use App\Models\User;
use App\Services\LoyverseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('registers a new customer with a hashed_id and saves the Loyverse customer ID', function () {
    mock(LoyverseService::class)
        ->shouldReceive('createCustomer')
        ->once()
        ->withArgs(fn (array $payload) => ! empty($payload['customer_code']))
        ->andReturn('loyverse-customer-uuid-123');

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', 'john@example.com');

    $user = User::where('email', 'john@example.com')->first();

    expect($user->hashed_id)->not->toBeEmpty();
    $response->assertJsonPath('user.hashed_id', $user->hashed_id);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'loyverse_customer_id' => 'loyverse-customer-uuid-123',
    ]);
    $this->assertDatabaseHas('loyalty_points', ['user_id' => $user->id]);
    expect($user->roles()->where('name', 'customer')->exists())->toBeTrue();
});

it('registers successfully even when Loyverse API is unavailable and hashed_id is still generated', function () {
    mock(LoyverseService::class)
        ->shouldReceive('createCustomer')
        ->once()
        ->andReturn(null);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(201);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->hashed_id)->not->toBeEmpty();
    expect($user->loyverse_customer_id)->toBeNull();
});

it('assigns customer role on registration', function () {
    mock(LoyverseService::class)
        ->shouldReceive('createCustomer')
        ->once()
        ->andReturn(null);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->hasRole('customer'))->toBeTrue();
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'dupe@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dupe',
        'email' => 'dupe@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('logs in with email', function () {
    $user = User::factory()->create(['password' => Hash::make('secret')]);

    $this->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'secret',
    ])->assertSuccessful()->assertJsonStructure(['user', 'token']);
});

it('logs in with username', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'password' => Hash::make('secret'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'login' => 'testuser',
        'password' => 'secret',
    ])->assertSuccessful()->assertJsonStructure(['user', 'token']);
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('correct')]);

    $this->postJson('/api/v1/auth/login', [
        'login' => 'user@example.com',
        'password' => 'wrong',
    ])->assertStatus(422)->assertJsonValidationErrors(['login']);
});

it('logs out and revokes the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logged out successfully.');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
});
