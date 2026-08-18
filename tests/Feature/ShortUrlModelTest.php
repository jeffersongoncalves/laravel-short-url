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

it('finds a short url by key, ignoring custom-domain-scoped ones', function () {
    ShortUrl::factory()->create(['url_key' => 'findme1']);
    ShortUrl::factory()->create(['url_key' => 'findme1', 'custom_domain_id' => 1]);

    expect(ShortUrl::findByKey('findme1')?->custom_domain_id)->toBeNull()
        ->and(ShortUrl::findByKey('missing-key'))->toBeNull();
});

it('scopes to enabled short urls only', function () {
    ShortUrl::factory()->create(['url_key' => 'en1', 'is_enabled' => true]);
    ShortUrl::factory()->create(['url_key' => 'en2', 'is_enabled' => false]);

    expect(ShortUrl::query()->enabled()->pluck('url_key')->all())->toBe(['en1']);
});
