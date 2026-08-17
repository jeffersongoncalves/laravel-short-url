<?php

use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Services\KeyGenerator;

afterEach(function () {
    Str::createRandomStringsNormally();
});

it('generates a key with the configured length', function () {
    config(['short-url.key.length' => 9]);

    $key = app(KeyGenerator::class)->generate();

    expect($key)->toHaveLength(9);
});

it('regenerates a key when the first attempt is blacklisted', function () {
    config(['short-url.key.blacklist' => ['blocked']]);
    Str::createRandomStringsUsingSequence(['blocked', 'abcdefg']);

    $key = app(KeyGenerator::class)->generate();

    expect($key)->toBe('abcdefg');
});

it('regenerates a key when there is a collision', function () {
    ShortUrl::factory()->create(['url_key' => 'dup1234', 'custom_domain_id' => null]);
    Str::createRandomStringsUsingSequence(['dup1234', 'unique1']);

    $key = app(KeyGenerator::class)->generate();

    expect($key)->toBe('unique1');
});

it('scopes key uniqueness per custom domain id', function () {
    ShortUrl::factory()->create(['url_key' => 'dup1234', 'custom_domain_id' => 1]);
    Str::createRandomStringsUsingSequence(['dup1234']);

    $key = app(KeyGenerator::class)->generate(customDomainId: 2);

    expect($key)->toBe('dup1234');
});

it('throws after exceeding the max attempts', function () {
    config(['short-url.key.blacklist' => ['x']]);
    Str::createRandomStringsUsingSequence(array_fill(0, KeyGenerator::MAX_ATTEMPTS, 'x'));

    app(KeyGenerator::class)->generate();
})->throws(RuntimeException::class);
