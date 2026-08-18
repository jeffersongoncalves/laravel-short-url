<?php

use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('lists links', function () {
    ShortUrl::factory()->count(3)->create();

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/links')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a link', function () {
    $response = $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links', ['destination_url' => 'https://example.com/target'])
        ->assertCreated();

    $response->assertJsonPath('data.destination_url', 'https://example.com/target');
    expect(ShortUrl::query()->count())->toBe(1);
});

it('hashes a password supplied on create', function () {
    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links', ['destination_url' => 'https://example.com', 'password' => 'secret'])
        ->assertCreated();

    $shortUrl = ShortUrl::query()->first();

    expect($shortUrl->password_hash)->not->toBe('secret')
        ->and(Hash::check('secret', $shortUrl->password_hash))->toBeTrue();
});

it('rejects an invalid destination url on create', function () {
    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links', ['destination_url' => 'not-a-url'])
        ->assertStatus(422);
});

it('shows a single link by uuid', function () {
    $shortUrl = ShortUrl::factory()->create();

    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson("/api/short-url/v1/links/{$shortUrl->uuid}")
        ->assertOk()
        ->assertJsonPath('data.id', $shortUrl->uuid);
});

it('returns 404 for an unknown uuid', function () {
    $this->withHeaders(apiHeaders(['links:read']))
        ->getJson('/api/short-url/v1/links/00000000-0000-0000-0000-000000000000')
        ->assertStatus(404);
});

it('updates a link', function () {
    $shortUrl = ShortUrl::factory()->create(['title' => 'Old']);

    $this->withHeaders(apiHeaders(['links:write']))
        ->patchJson("/api/short-url/v1/links/{$shortUrl->uuid}", ['title' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.title', 'New');
});

it('soft deletes and restores a link', function () {
    $shortUrl = ShortUrl::factory()->create();

    $this->withHeaders(apiHeaders(['links:write']))
        ->deleteJson("/api/short-url/v1/links/{$shortUrl->uuid}")
        ->assertStatus(204);

    expect(ShortUrl::query()->find($shortUrl->id))->toBeNull()
        ->and(ShortUrl::withTrashed()->find($shortUrl->id))->not->toBeNull();

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson("/api/short-url/v1/links/{$shortUrl->uuid}/restore")
        ->assertOk();

    expect(ShortUrl::query()->find($shortUrl->id))->not->toBeNull();
});

it('bulk creates up to the request limit and reports per-row errors', function () {
    $response = $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links/bulk', [
            'links' => [
                ['destination_url' => 'https://a.example'],
                ['destination_url' => 'https://b.example'],
            ],
        ])
        ->assertCreated();

    $response->assertJsonCount(2, 'data');
    expect(ShortUrl::query()->count())->toBe(2);
});

it('rejects a bulk request over 500 links', function () {
    $links = array_fill(0, 501, ['destination_url' => 'https://example.com']);

    $this->withHeaders(apiHeaders(['links:write']))
        ->postJson('/api/short-url/v1/links/bulk', ['links' => $links])
        ->assertStatus(422);
});
