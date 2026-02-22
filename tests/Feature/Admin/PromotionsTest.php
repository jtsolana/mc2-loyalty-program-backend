<?php

use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeAdminForPromotions(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

it('admin can view promotions index', function () {
    $admin = makeAdminForPromotions();
    Promotion::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/admin/promotions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('admin/promotions/index'));
});

it('admin can create a promotion', function () {
    Storage::fake('public');
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'Summer Sale',
            'excerpt' => 'Get 20% off all items this summer.',
            'content' => '<p>Full content here.</p>',
            'type' => 'promotion',
            'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg'),
            'is_published' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', ['title' => 'Summer Sale', 'type' => 'promotion', 'is_published' => true]);

    $promotion = Promotion::where('title', 'Summer Sale')->first();
    Storage::disk('public')->assertExists($promotion->thumbnail);
});

it('admin can create a promotion without thumbnail', function () {
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'New Announcement',
            'excerpt' => 'We have some exciting news.',
            'content' => '<p>Read more here.</p>',
            'type' => 'announcement',
            'is_published' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', ['title' => 'New Announcement', 'type' => 'announcement', 'is_published' => false]);
});

it('admin can update a promotion', function () {
    Storage::fake('public');
    $admin = makeAdminForPromotions();
    $promotion = Promotion::factory()->create(['title' => 'Old Title']);

    $this->actingAs($admin)
        ->post("/admin/promotions/{$promotion->hashed_id}", [
            'title' => 'Updated Title',
            'excerpt' => 'Updated excerpt.',
            'content' => '<p>Updated content.</p>',
            'type' => 'announcement',
            'is_published' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'title' => 'Updated Title', 'type' => 'announcement']);
});

it('admin can delete a promotion', function () {
    Storage::fake('public');
    $admin = makeAdminForPromotions();
    $promotion = Promotion::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/promotions/{$promotion->hashed_id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
});

it('admin can delete a promotion with thumbnail and file is removed', function () {
    Storage::fake('public');
    $admin = makeAdminForPromotions();

    $file = UploadedFile::fake()->image('thumb.jpg');
    $path = $file->store('promotions', 'public');
    $promotion = Promotion::factory()->create(['thumbnail' => $path]);

    $this->actingAs($admin)
        ->delete("/admin/promotions/{$promotion->hashed_id}")
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
    $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
});

it('promotion creation fails with missing required fields', function () {
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', ['title' => 'Only Title'])
        ->assertSessionHasErrors(['excerpt', 'content', 'type', 'is_published']);
});

it('unauthenticated user cannot access admin promotions', function () {
    $this->get('/admin/promotions')->assertRedirect('/login');
});
