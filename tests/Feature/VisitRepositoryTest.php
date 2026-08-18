<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

function makeVisit(ShortUrl $shortUrl, array $overrides = []): void
{
    Visit::query()->create(array_merge([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'device_type' => 'desktop',
        'browser' => 'Chrome',
        'operating_system' => 'Windows',
        'country_code' => 'BR',
        'referer_host' => 'google.com',
        'referer_type' => 'search',
        'is_bot' => false,
        'is_qr_scan' => false,
        'ip_hash' => bin2hex(random_bytes(8)),
        'created_at' => now(),
    ], $overrides));
}

it('stores a visit row', function () {
    $shortUrl = ShortUrl::factory()->create();

    app(VisitRepository::class)->store([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'is_bot' => false,
        'created_at' => now(),
    ]);

    expect(Visit::query()->where('short_url_id', $shortUrl->id)->count())->toBe(1);
});

it('queries visits filtered by column', function () {
    $shortUrl = ShortUrl::factory()->create();
    makeVisit($shortUrl, ['country_code' => 'BR']);
    makeVisit($shortUrl, ['country_code' => 'US']);

    $rows = app(VisitRepository::class)->query($shortUrl->id, ['country_code' => 'BR']);

    expect($rows)->toHaveCount(1);
});

it('aggregates visit counts and per-dimension stats', function () {
    $shortUrl = ShortUrl::factory()->create();
    makeVisit($shortUrl, ['country_code' => 'BR', 'is_bot' => false]);
    makeVisit($shortUrl, ['country_code' => 'BR', 'is_bot' => false]);
    makeVisit($shortUrl, ['country_code' => 'US', 'is_bot' => true]);

    $stats = app(VisitRepository::class)->aggregate($shortUrl->id, now()->subDay(), now()->addDay());

    expect($stats['visits_count'])->toBe(2)
        ->and($stats['bot_visits_count'])->toBe(1)
        ->and($stats['country_stats'])->toBe(['BR' => 2, 'US' => 1]);
});

it('aggregates across a set of short url ids and excludes the rest', function () {
    $linkA = ShortUrl::factory()->create();
    $linkB = ShortUrl::factory()->create();
    $excluded = ShortUrl::factory()->create();
    makeVisit($linkA, ['country_code' => 'BR', 'is_bot' => false]);
    makeVisit($linkB, ['country_code' => 'US', 'is_bot' => false]);
    makeVisit($excluded, ['country_code' => 'FR', 'is_bot' => false]);

    $stats = app(VisitRepository::class)->aggregateMany([$linkA->id, $linkB->id], now()->subDay(), now()->addDay());

    expect($stats['visits_count'])->toBe(2)
        ->and($stats['country_stats'])->toBe(['BR' => 1, 'US' => 1]);
});

it('returns empty totals from aggregateMany([]) without querying every visit', function () {
    $shortUrl = ShortUrl::factory()->create();
    makeVisit($shortUrl, ['country_code' => 'BR']);

    $stats = app(VisitRepository::class)->aggregateMany([], now()->subDay(), now()->addDay());

    expect($stats['visits_count'])->toBe(0)
        ->and($stats['country_stats'])->toBe([]);
});

it('prunes visits older than a given date', function () {
    $shortUrl = ShortUrl::factory()->create();
    makeVisit($shortUrl, ['visited_at' => now()->subDays(400)]);
    makeVisit($shortUrl, ['visited_at' => now()]);

    $pruned = app(VisitRepository::class)->prune(now()->subDays(30));

    expect($pruned)->toBe(1)
        ->and(Visit::query()->count())->toBe(1);
});

it('only prunes the given tenant\'s old visits when a tenant id is passed', function () {
    $shortUrl = ShortUrl::factory()->create();
    makeVisit($shortUrl, ['visited_at' => now()->subDays(400), 'tenant_id' => 1]);
    makeVisit($shortUrl, ['visited_at' => now()->subDays(400), 'tenant_id' => 2]);

    $pruned = app(VisitRepository::class)->prune(now()->subDays(30), 1);

    expect($pruned)->toBe(1)
        ->and(Visit::query()->count())->toBe(1)
        ->and(Visit::query()->first()->tenant_id)->toBe(2);
});
