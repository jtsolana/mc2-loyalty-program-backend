<?php

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;

it('returns the company terms', function () {
    CompanyProfile::factory()->create(['terms' => 'Our terms and conditions.']);

    $this->getJson('/api/v1/terms')
        ->assertSuccessful()
        ->assertJsonPath('data', 'Our terms and conditions.');
});

it('caches the terms response', function () {
    CompanyProfile::factory()->create(['terms' => 'Cached terms.']);

    $this->getJson('/api/v1/terms')->assertSuccessful();

    expect(Cache::has('company_terms'))->toBeTrue();
});
