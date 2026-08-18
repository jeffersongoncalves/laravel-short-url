<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use JeffersonGoncalves\LaravelShortUrl\Registries\AnalyticsDriverRegistry;

it('registers ga4 and plausible by default', function () {
    $registry = app(AnalyticsDriverRegistry::class);

    expect($registry->driver('ga4'))->toBeInstanceOf(AnalyticsDriver::class)
        ->and($registry->driver('plausible'))->toBeInstanceOf(AnalyticsDriver::class)
        ->and($registry->driver('unknown'))->toBeNull();
});

it('only returns drivers enabled in config', function () {
    config([
        'short-url.analytics.ga4.enabled' => true,
        'short-url.analytics.plausible.enabled' => false,
    ]);

    $registry = app(AnalyticsDriverRegistry::class);

    expect($registry->enabledDrivers())->toHaveCount(1);
});

it('allows registering a custom driver via extend', function () {
    $registry = new AnalyticsDriverRegistry;
    $registry->extend('custom', fn () => new class implements AnalyticsDriver
    {
        public function record(array $visit): void {}
    });

    expect($registry->driver('custom'))->toBeInstanceOf(AnalyticsDriver::class);
});
