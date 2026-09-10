<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;

afterEach(function () {
    config(['short-url.tenancy.enabled' => false]);
});

it('returns an empty array for an empty input', function () {
    expect(app(ShortUrlManager::class)->resolveMany([]))->toBe([]);
});

it('dedupes input urls in the result', function () {
    $results = app(ShortUrlManager::class)->resolveMany([
        'https://example.com/a',
        'https://example.com/a',
    ]);

    expect($results)->toHaveCount(1)
        ->and($results)->toHaveKey('https://example.com/a');
});

it('mints a new short url for a genuinely new destination', function () {
    $results = app(ShortUrlManager::class)->resolveMany(['https://example.com/new']);

    $shortUrl = ShortUrl::query()->where('destination_url', 'https://example.com/new')->first();

    expect($shortUrl)->not->toBeNull()
        ->and($results['https://example.com/new'])->toBe($shortUrl->fullUrl());
});

it('reuses an already existing short url for the same destination instead of creating a duplicate', function () {
    $existing = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com/existing']);

    $results = app(ShortUrlManager::class)->resolveMany(['https://example.com/existing']);

    expect($results['https://example.com/existing'])->toBe($existing->fullUrl())
        ->and(ShortUrl::query()->where('destination_url', 'https://example.com/existing')->count())->toBe(1);
});

it('reuses a cached destination lookup without hitting the database again', function () {
    $first = app(ShortUrlManager::class)->resolveMany(['https://example.com/cached']);
    $shortUrl = ShortUrl::query()->where('destination_url', 'https://example.com/cached')->first();

    // Bypasses Eloquent events (and so the observer's cache invalidation)
    // to prove the second resolveMany() call is served from cache alone.
    DB::table($shortUrl->getTable())->where('id', $shortUrl->id)->delete();

    $second = app(ShortUrlManager::class)->resolveMany(['https://example.com/cached']);

    expect($second['https://example.com/cached'])->toBe($first['https://example.com/cached']);
});

it('falls back to the original url when minting a key fails, without losing the rest of the batch', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.links_per_month' => 1,
    ]);

    $results = app(ShortUrlManager::class)->resolveMany([
        'https://example.com/one',
        'https://example.com/two',
    ]);

    expect($results['https://example.com/one'])->not->toBe('https://example.com/one')
        ->and($results['https://example.com/two'])->toBe('https://example.com/two');
});

it('scopes cached destination lookups per tenant', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
    ]);
    app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com/shared']);

    config(['short-url.tenancy.current_tenant_id' => 2]);
    $results = app(ShortUrlManager::class)->resolveMany(['https://example.com/shared']);

    $tenantTwoShortUrl = ShortUrl::query()->where('destination_url', 'https://example.com/shared')->first();

    expect($tenantTwoShortUrl->tenant_id)->toBe(2)
        ->and($results['https://example.com/shared'])->toBe($tenantTwoShortUrl->fullUrl());
});
