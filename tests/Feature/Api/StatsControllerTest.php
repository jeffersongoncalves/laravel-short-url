<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('returns aggregated stats for a link', function () {
    $shortUrl = ShortUrl::factory()->create();
    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'country_code' => 'BR',
        'is_bot' => false,
        'ip_hash' => 'hash-1',
        'created_at' => now(),
    ]);

    $response = $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/links/{$shortUrl->uuid}/stats")
        ->assertOk();

    $response->assertJsonPath('data.totalVisits', 1);
});
