<?php

use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl as ShortUrlModel;

it('creates a short url via the facade, generating a key when omitted', function () {
    $shortUrl = ShortUrl::create(['destination_url' => 'https://example.com']);

    expect($shortUrl->exists)->toBeTrue()
        ->and($shortUrl->url_key)->not->toBeEmpty();
});

it('builds a destination fluently via the facade', function () {
    $shortUrl = ShortUrl::destination('https://example.com')
        ->key('facade1')
        ->title('My link')
        ->maxVisits(10)
        ->singleUse()
        ->create();

    expect($shortUrl->fresh())
        ->destination_url->toBe('https://example.com')
        ->url_key->toBe('facade1')
        ->title->toBe('My link')
        ->max_visits->toBe(10)
        ->single_use->toBeTrue();
});

it('resolves an existing short url by key and returns null when missing', function () {
    ShortUrlModel::factory()->create(['url_key' => 'resolve1']);

    expect(ShortUrl::resolve('resolve1'))->not->toBeNull()
        ->and(ShortUrl::resolve('missing-key'))->toBeNull();
});
