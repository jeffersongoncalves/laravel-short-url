<?php

use JeffersonGoncalves\LaravelShortUrl\Data\FilterType;
use JeffersonGoncalves\LaravelShortUrl\Registries\FilterTypeRegistry;

it('registers all default filter types on boot', function () {
    $registry = app(FilterTypeRegistry::class);

    expect($registry->get('device'))->toBeInstanceOf(FilterType::class)
        ->and($registry->all())->toHaveKey('is_bot')
        ->and(array_keys($registry->all()))->toContain('country', 'datetime', 'query_param');
});

it('allows registering a custom filter type', function () {
    $registry = new FilterTypeRegistry;
    $registry->register(new FilterType('custom', 'Custom', 'text'));

    expect($registry->get('custom')->label)->toBe('Custom')
        ->and($registry->get('missing'))->toBeNull();
});
