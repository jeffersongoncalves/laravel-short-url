<?php

use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('casts attributes to the expected types', function () {
    $shortUrl = ShortUrl::factory()->create([
        'targeting_rules' => ['weight' => 1],
        'is_enabled' => 1,
        'expires_at' => now(),
    ]);

    expect($shortUrl->targeting_rules)->toBeArray()
        ->and($shortUrl->is_enabled)->toBeBool()
        ->and($shortUrl->expires_at)->toBeInstanceOf(Carbon::class);
});

it('generates a uuid automatically on creation', function () {
    $shortUrl = ShortUrl::factory()->create();

    expect($shortUrl->uuid)->not->toBeNull()->toBeString();
});

it('resolves route model binding by url_key', function () {
    ShortUrl::factory()->create(['url_key' => 'abc1234']);

    expect((new ShortUrl)->getRouteKeyName())->toBe('url_key');
});

it('hides the password hash from array output', function () {
    $shortUrl = ShortUrl::factory()->create(['password_hash' => 'secret']);

    expect($shortUrl->toArray())->not->toHaveKey('password_hash');
});

it('builds a short url fluently', function () {
    $shortUrl = ShortUrl::make()
        ->to('https://example.com')
        ->key('fluent1')
        ->title('My link')
        ->maxVisits(10)
        ->singleUse();

    $shortUrl->save();

    expect($shortUrl->fresh())
        ->destination_url->toBe('https://example.com')
        ->url_key->toBe('fluent1')
        ->title->toBe('My link')
        ->max_visits->toBe(10)
        ->single_use->toBeTrue();
});
