<?php

use JeffersonGoncalves\LaravelShortUrl\Data\PixelProvider;
use JeffersonGoncalves\LaravelShortUrl\Registries\PixelProviderRegistry;

it('registers default providers on boot', function () {
    $registry = app(PixelProviderRegistry::class);

    expect(array_keys($registry->all()))->toContain('meta_pixel', 'google_ads', 'tiktok_pixel', 'google_analytics');
});

it('renders a provider script with the config placeholders replaced', function () {
    $registry = app(PixelProviderRegistry::class);
    $provider = $registry->get('meta_pixel');

    $script = $provider->render(['pixel_id' => '123456']);

    expect($script)->toContain("fbq('init', '123456')")
        ->and($script)->not->toContain('{pixel_id}');
});

it('allows registering a custom provider', function () {
    $registry = new PixelProviderRegistry;
    $registry->register(new PixelProvider('custom', 'Custom', [], 'console.log("{x}")'));

    expect($registry->get('custom')->render(['x' => '1']))->toBe('console.log("1")')
        ->and($registry->get('missing'))->toBeNull();
});
