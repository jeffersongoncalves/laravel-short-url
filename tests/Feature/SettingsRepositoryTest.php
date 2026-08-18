<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;

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

it('exposes a declarative schema with translated labels', function () {
    $settings = app(SettingsRepository::class);

    $schema = $settings->schema();

    expect($schema)->toHaveKey('key.length')
        ->and($schema['key.length']['default'])->toBe(7)
        ->and($schema['key.length']['label'])->toBe('Key length');
});
