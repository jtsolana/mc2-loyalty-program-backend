<?php

use App\Mail\ContactSupportMail;
use App\Models\CompanyProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    CompanyProfile::factory()->create(['email' => 'support@test.com']);

    $this->customer = User::factory()->create();
    $this->customer->roles()->attach(Role::where('name', 'customer')->first());
});

it('sends a contact support email to the company email', function () {
    Mail::fake();

    $this->actingAs($this->customer, 'sanctum')
        ->postJson('/api/v1/customer/help-center', [
            'subject' => 'Need help',
            'message' => 'I have a question about my points.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Your message has been sent to our support team. We will get back to you shortly.');

    Mail::assertQueued(ContactSupportMail::class, function (ContactSupportMail $mail) {
        return $mail->hasTo('support@test.com')
            && $mail->userSubject === 'Need help'
            && $mail->userMessage === 'I have a question about my points.';
    });
});

it('validates required fields for contact support', function () {
    $this->actingAs($this->customer, 'sanctum')
        ->postJson('/api/v1/customer/help-center', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'message']);
});

it('validates max length for contact support fields', function () {
    $this->actingAs($this->customer, 'sanctum')
        ->postJson('/api/v1/customer/help-center', [
            'subject' => str_repeat('a', 256),
            'message' => str_repeat('a', 2001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'message']);
});

it('rate limits contact support requests', function () {
    Mail::fake();

    for ($i = 0; $i < 3; $i++) {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/customer/help-center', [
                'subject' => 'Help',
                'message' => 'Message',
            ])
            ->assertSuccessful();
    }

    $this->actingAs($this->customer, 'sanctum')
        ->postJson('/api/v1/customer/help-center', [
            'subject' => 'Help',
            'message' => 'Message',
        ])
        ->assertStatus(429);
});

it('requires authentication to contact support', function () {
    $this->postJson('/api/v1/customer/help-center', [
        'subject' => 'Help',
        'message' => 'Message',
    ])->assertUnauthorized();
});
