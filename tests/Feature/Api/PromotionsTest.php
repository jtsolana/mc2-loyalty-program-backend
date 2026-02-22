<?php

use App\Models\Promotion;

it('returns published promotions publicly', function () {
    Promotion::factory()->count(3)->create(['is_published' => true]);
    Promotion::factory()->unpublished()->create();

    $this->getJson('/api/v1/promotions')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('does not return unpublished promotions', function () {
    Promotion::factory()->unpublished()->count(2)->create();

    $this->getJson('/api/v1/promotions')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('can filter promotions by type', function () {
    Promotion::factory()->promotion()->count(2)->create(['is_published' => true]);
    Promotion::factory()->announcement()->create(['is_published' => true]);

    $this->getJson('/api/v1/promotions?type=promotion')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/promotions?type=announcement')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('returns a single published promotion', function () {
    $promotion = Promotion::factory()->create(['is_published' => true, 'title' => 'Big Sale']);

    $this->getJson("/api/v1/promotions/{$promotion->hashed_id}")
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Big Sale');
});

it('returns 404 for unpublished promotion', function () {
    $promotion = Promotion::factory()->unpublished()->create();

    $this->getJson("/api/v1/promotions/{$promotion->hashed_id}")
        ->assertNotFound();
});

it('promotion response includes expected fields', function () {
    $promotion = Promotion::factory()->create(['is_published' => true]);

    $this->getJson("/api/v1/promotions/{$promotion->hashed_id}")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id', 'title', 'excerpt', 'thumbnail_url', 'content', 'type', 'is_published', 'published_at', 'created_at',
            ],
        ]);
});
