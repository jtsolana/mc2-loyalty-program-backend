<?php

use App\Models\CompanyProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeAdminForCompany(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can view the company profile edit page', function () {
    $admin = makeAdminForCompany();

    $this->actingAs($admin)
        ->get('/admin/company-profile')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('admin/company-profile/edit'));
});

it('admin can update company profile text fields', function () {
    $admin = makeAdminForCompany();

    $this->actingAs($admin)
        ->post('/admin/company-profile', [
            'name' => 'Acme Corp',
            'address' => '123 Main Street',
            'contact_number' => '+1-555-0100',
            'email' => 'info@acme.com',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('company_profiles', [
        'name' => 'Acme Corp',
        'address' => '123 Main Street',
        'contact_number' => '+1-555-0100',
        'email' => 'info@acme.com',
    ]);
});

it('admin can upload a company logo', function () {
    Storage::fake('public');
    $admin = makeAdminForCompany();

    $this->actingAs($admin)
        ->post('/admin/company-profile', [
            'name' => 'Logo Company',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])
        ->assertRedirect();

    $profile = CompanyProfile::first();
    expect($profile->logo)->not->toBeNull();
    Storage::disk('public')->assertExists($profile->logo);
});

it('uploading a new logo replaces the old one', function () {
    Storage::fake('public');
    $admin = makeAdminForCompany();

    $oldFile = UploadedFile::fake()->image('old-logo.png');
    $oldPath = $oldFile->store('company', 'public');
    CompanyProfile::getSingleton()->update(['logo' => $oldPath]);

    $this->actingAs($admin)
        ->post('/admin/company-profile', [
            'logo' => UploadedFile::fake()->image('new-logo.png'),
        ])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($oldPath);

    $profile = CompanyProfile::first();
    Storage::disk('public')->assertExists($profile->logo);
});

it('company profile creates a singleton on first access', function () {
    $admin = makeAdminForCompany();

    expect(CompanyProfile::count())->toBe(0);

    $this->actingAs($admin)->get('/admin/company-profile')->assertSuccessful();

    expect(CompanyProfile::count())->toBe(1);
});

it('update validates email format', function () {
    $admin = makeAdminForCompany();

    $this->actingAs($admin)
        ->post('/admin/company-profile', [
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors(['email']);
});

it('unauthenticated user cannot access company profile', function () {
    $this->get('/admin/company-profile')->assertRedirect('/login');
});
