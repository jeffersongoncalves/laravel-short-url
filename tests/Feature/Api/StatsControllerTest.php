<?php

use JeffersonGoncalves\LaravelShortUrl\Models\Folder;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Tag;
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

it('returns aggregated stats across every link with the global endpoint', function () {
    $linkA = ShortUrl::factory()->create();
    $linkB = ShortUrl::factory()->create();
    Visit::query()->create([
        'short_url_id' => $linkA->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'a1', 'created_at' => now(),
    ]);
    Visit::query()->create([
        'short_url_id' => $linkB->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'b1', 'created_at' => now(),
    ]);

    $response = $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/stats')
        ->assertOk();

    $response->assertJsonPath('data.totalVisits', 2);
});

it('scopes the global stats endpoint to a folder_id filter', function () {
    $folder = Folder::factory()->create();
    $inFolder = ShortUrl::factory()->create(['folder_id' => $folder->id]);
    $outsideFolder = ShortUrl::factory()->create();
    Visit::query()->create([
        'short_url_id' => $inFolder->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'a1', 'created_at' => now(),
    ]);
    Visit::query()->create([
        'short_url_id' => $outsideFolder->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'b1', 'created_at' => now(),
    ]);

    $response = $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/stats?folder_id={$folder->id}")
        ->assertOk();

    $response->assertJsonPath('data.totalVisits', 1);
});

it('scopes the global stats endpoint to a tag_id filter', function () {
    $tag = Tag::factory()->create();
    $tagged = ShortUrl::factory()->create();
    $tagged->tags()->attach($tag);
    $untagged = ShortUrl::factory()->create();
    Visit::query()->create([
        'short_url_id' => $tagged->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'a1', 'created_at' => now(),
    ]);
    Visit::query()->create([
        'short_url_id' => $untagged->id, 'visited_at' => now(), 'is_bot' => false, 'ip_hash' => 'b1', 'created_at' => now(),
    ]);

    $response = $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/stats?tag_id={$tag->id}")
        ->assertOk();

    $response->assertJsonPath('data.totalVisits', 1);
});
