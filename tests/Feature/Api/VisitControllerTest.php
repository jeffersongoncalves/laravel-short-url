<?php

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('lists visits for a link', function () {
    $shortUrl = ShortUrl::factory()->create();
    Visit::query()->create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'country_code' => 'BR',
        'is_bot' => false,
        'created_at' => now(),
    ]);

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/links/{$shortUrl->uuid}/visits")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters visits by column', function () {
    $shortUrl = ShortUrl::factory()->create();
    Visit::query()->create(['short_url_id' => $shortUrl->id, 'visited_at' => now(), 'country_code' => 'BR', 'is_bot' => false, 'created_at' => now()]);
    Visit::query()->create(['short_url_id' => $shortUrl->id, 'visited_at' => now(), 'country_code' => 'US', 'is_bot' => false, 'created_at' => now()]);

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/links/{$shortUrl->uuid}/visits?country_code=BR")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
