<?php

use JeffersonGoncalves\LaravelShortUrl\Data\AppDefinition;
use JeffersonGoncalves\LaravelShortUrl\Registries\DeepLinkRegistry;

it('registers default apps on boot', function () {
    $registry = app(DeepLinkRegistry::class);

    expect(array_keys($registry->all()))->toContain('instagram', 'youtube', 'spotify');
});

it('resolves the matching app for a destination url', function () {
    $registry = app(DeepLinkRegistry::class);

    $app = $registry->forUrl('https://www.instagram.com/someuser');

    expect($app?->key)->toBe('instagram');
});

it('returns null when no app matches', function () {
    $registry = app(DeepLinkRegistry::class);

    expect($registry->forUrl('https://example.com/page'))->toBeNull();
});

it('builds a scheme url with the destination urlencoded', function () {
    $app = new AppDefinition('test', 'Test', ['example.com'], 'testapp://open?url={url}');

    expect($app->buildSchemeUrl('https://example.com/a b'))->toBe('testapp://open?url=https%3A%2F%2Fexample.com%2Fa+b');
});

it('matches a bare host and a subdomain of a registered host', function () {
    $app = new AppDefinition('test', 'Test', ['example.com'], 'testapp://{url}');

    expect($app->matches('example.com'))->toBeTrue()
        ->and($app->matches('sub.example.com'))->toBeTrue()
        ->and($app->matches('notexample.com'))->toBeFalse();
});
