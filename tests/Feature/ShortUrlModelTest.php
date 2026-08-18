<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
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

it('finds a short url by key, ignoring custom-domain-scoped ones', function () {
    ShortUrl::factory()->create(['url_key' => 'findme1']);
    ShortUrl::factory()->create(['url_key' => 'findme1', 'custom_domain_id' => 1]);

    expect(ShortUrl::findByKey('findme1')?->custom_domain_id)->toBe(0)
        ->and(ShortUrl::findByKey('missing-key'))->toBeNull();
});

it('enforces url_key uniqueness for two root-level (no custom domain) links', function () {
    // Regression test: custom_domain_id used to be nullable, and NULL is
    // never equal to NULL in a unique index (Postgres/MySQL/SQLite alike),
    // so unique(custom_domain_id, url_key) silently never engaged for the
    // common case. It's now a NOT NULL column defaulting to sentinel 0.
    ShortUrl::factory()->create(['url_key' => 'dupe1234']);

    // DB::transaction() wraps the failing insert in its own SAVEPOINT —
    // on Postgres, a caught constraint violation still aborts the whole
    // enclosing transaction (including RefreshDatabase's per-test one)
    // until something rolls back to a savepoint.
    expect(fn () => DB::transaction(fn () => ShortUrl::factory()->create(['url_key' => 'dupe1234'])))
        ->toThrow(QueryException::class);
});

it('allows the same url_key to exist once per custom domain and once at the root', function () {
    ShortUrl::factory()->create(['url_key' => 'shared01', 'custom_domain_id' => 1]);

    $rootLink = ShortUrl::factory()->create(['url_key' => 'shared01']);

    // The DB default only takes effect on INSERT — refresh() to read back
    // what actually landed in the column rather than the in-memory model,
    // which never had custom_domain_id set at all.
    expect($rootLink->refresh()->custom_domain_id)->toBe(0);
});

it('builds the full url against the app host by default', function () {
    // TestCase pins short-url.route.domain to "short.test" — that config,
    // when set, is authoritative over app.url's host (same priority
    // ShortUrlObserver's cache-key resolution uses).
    config(['short-url.route.prefix' => '']);

    $shortUrl = ShortUrl::factory()->create(['url_key' => 'aB3xK9']);

    expect($shortUrl->fullUrl())->toBe('https://short.test/aB3xK9');
});

it('falls back to app.url\'s host when no route.domain is configured', function () {
    config(['short-url.route.domain' => null, 'app.url' => 'https://app.test', 'short-url.route.prefix' => '']);

    $shortUrl = ShortUrl::factory()->create(['url_key' => 'aB3xK9']);

    expect($shortUrl->fullUrl())->toBe('https://app.test/aB3xK9');
});

it('builds the full url against the verified custom domain when set', function () {
    $domain = CustomDomain::factory()->create(['domain' => 'links.example.com', 'is_verified' => true]);

    $shortUrl = ShortUrl::factory()->create(['url_key' => 'aB3xK9', 'custom_domain_id' => $domain->id]);

    expect($shortUrl->fullUrl())->toBe('https://links.example.com/aB3xK9');
});

it('includes the configured route prefix in the full url', function () {
    config(['short-url.route.prefix' => 'go']);

    $shortUrl = ShortUrl::factory()->create(['url_key' => 'aB3xK9']);

    expect($shortUrl->fullUrl())->toBe('https://short.test/go/aB3xK9');
});

it('scopes to enabled short urls only', function () {
    ShortUrl::factory()->create(['url_key' => 'en1', 'is_enabled' => true]);
    ShortUrl::factory()->create(['url_key' => 'en2', 'is_enabled' => false]);

    expect(ShortUrl::query()->enabled()->pluck('url_key')->all())->toBe(['en1']);
});
