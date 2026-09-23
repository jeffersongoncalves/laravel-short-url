<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Settings\Setting;

it('stores and retrieves a setting value through the cache', function () {
    $settings = app(SettingsRepository::class);

    $settings->set('brand_name', 'Acme');

    expect($settings->get('brand_name'))->toBe('Acme');
});

it('returns the default when a setting is missing', function () {
    $settings = app(SettingsRepository::class);

    expect($settings->get('missing', 'fallback'))->toBe('fallback');
});

it('forgets a setting and its cache entry', function () {
    $settings = app(SettingsRepository::class);
    $settings->set('to_remove', 'value');

    $settings->forget('to_remove');

    expect($settings->get('to_remove'))->toBeNull();
});

it('does not cache the caller default for a missing setting', function () {
    $settings = app(SettingsRepository::class);

    expect($settings->get('missing', 'first'))->toBe('first')
        ->and($settings->get('missing', 'second'))->toBe('second');
});

it('applies stored settings over config when creating short urls', function () {
    config(['short-url.redirect.default_status_code' => 302, 'short-url.key.length' => 7]);
    $settings = app(SettingsRepository::class);
    $settings->set('redirect.default_status_code', 301);
    $settings->set('key.length', 10);

    $shortUrl = ShortUrl::create(['destination_url' => 'https://example.com']);

    expect($shortUrl->redirect_status_code)->toBe(301)
        ->and($shortUrl->url_key)->toHaveLength(10)
        ->and(Setting::int('cache.ttl', 3600))->toBe((int) config('short-url.cache.ttl', 3600));
});

it('falls back to config when no setting row exists', function () {
    config(['short-url.redirect.default_status_code' => 307, 'short-url.key.length' => 5, 'short-url.cache.ttl' => 60]);

    $shortUrl = ShortUrl::create(['destination_url' => 'https://example.com']);

    expect($shortUrl->redirect_status_code)->toBe(307)
        ->and($shortUrl->url_key)->toHaveLength(5)
        ->and(Setting::int('cache.ttl', 3600))->toBe(60)
        ->and(app(SettingsRepository::class)->schema()['redirect.default_status_code']['default'])->toBe(307);
});

it('reads cache ttl from a stored setting', function () {
    app(SettingsRepository::class)->set('cache.ttl', 120);

    expect(Setting::int('cache.ttl', 3600))->toBe(120);
});

it('exposes a declarative schema with translated labels', function () {
    $settings = app(SettingsRepository::class);

    $schema = $settings->schema();

    expect($schema)->toHaveKey('key.length')
        ->and($schema['key.length']['default'])->toBe(7)
        ->and($schema['key.length']['label'])->toBe('Key length');
});
