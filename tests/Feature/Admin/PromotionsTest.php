<?php

use App\Console\Commands\PublishScheduledPromotionsCommand;
use App\Jobs\SendPushNotificationToCustomers;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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

it('admin can create a published promotion', function () {
    Queue::fake();
    Storage::fake('public');
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'Summer Sale',
            'excerpt' => 'Get 20% off all items this summer.',
            'content' => '<p>Full content here.</p>',
            'type' => 'promotion',
            'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg'),
            'publish_status' => 'published',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'title' => 'Summer Sale',
        'type' => 'promotion',
        'publish_status' => 'published',
        'is_published' => true,
    ]);

    $promotion = Promotion::where('title', 'Summer Sale')->first();
    Storage::disk('public')->assertExists($promotion->thumbnail);
    Queue::assertPushed(SendPushNotificationToCustomers::class);
});

it('admin can create a draft promotion without thumbnail', function () {
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'New Announcement',
            'excerpt' => 'We have some exciting news.',
            'content' => '<p>Read more here.</p>',
            'type' => 'announcement',
            'publish_status' => 'draft',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'title' => 'New Announcement',
        'type' => 'announcement',
        'publish_status' => 'draft',
        'is_published' => false,
    ]);
});

it('admin can create a scheduled promotion', function () {
    Queue::fake();
    $admin = makeAdminForPromotions();
    $publishAt = now()->addDay()->format('Y-m-d H:i:s');

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'Upcoming Promo',
            'excerpt' => 'Coming soon.',
            'content' => '<p>Stay tuned.</p>',
            'type' => 'promotion',
            'publish_status' => 'scheduled',
            'published_at' => $publishAt,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'title' => 'Upcoming Promo',
        'publish_status' => 'scheduled',
        'is_published' => false,
    ]);

    Queue::assertNotPushed(SendPushNotificationToCustomers::class);
});

it('admin can create a promotion with an expiry date', function () {
    $admin = makeAdminForPromotions();
    $expiresAt = now()->addDays(7)->format('Y-m-d H:i:s');

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'Limited Offer',
            'excerpt' => 'Only for a week.',
            'content' => '<p>Hurry up.</p>',
            'type' => 'promotion',
            'publish_status' => 'published',
            'expires_at' => $expiresAt,
        ])
        ->assertRedirect();

    $promotion = Promotion::where('title', 'Limited Offer')->first();
    expect($promotion->expires_at)->not->toBeNull();
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
            'publish_status' => 'published',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'id' => $promotion->id,
        'title' => 'Updated Title',
        'type' => 'announcement',
        'publish_status' => 'published',
    ]);
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
        ->assertSessionHasErrors(['excerpt', 'content', 'type', 'publish_status']);
});

it('scheduled promotion requires published_at', function () {
    $admin = makeAdminForPromotions();

    $this->actingAs($admin)
        ->post('/admin/promotions', [
            'title' => 'Missing Date',
            'excerpt' => 'No date provided.',
            'content' => '<p>Content.</p>',
            'type' => 'promotion',
            'publish_status' => 'scheduled',
        ])
        ->assertSessionHasErrors(['published_at']);
});

it('publish scheduled promotions command publishes due promotions and dispatches push notifications', function () {
    Queue::fake();

    Promotion::factory()->scheduled()->create([
        'title' => 'Due Promo',
        'published_at' => now()->subMinute(),
    ]);

    Promotion::factory()->scheduled()->create([
        'title' => 'Future Promo',
        'published_at' => now()->addHour(),
    ]);

    $this->artisan(PublishScheduledPromotionsCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseHas('promotions', ['title' => 'Due Promo', 'is_published' => true, 'publish_status' => 'published']);
    $this->assertDatabaseHas('promotions', ['title' => 'Future Promo', 'is_published' => false, 'publish_status' => 'scheduled']);

    Queue::assertPushed(SendPushNotificationToCustomers::class, 1);
});

it('unauthenticated user cannot access admin promotions', function () {
    $this->get('/admin/promotions')->assertRedirect('/login');
});
