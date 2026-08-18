<?php

use JeffersonGoncalves\LaravelShortUrl\Exceptions\PlanLimitExceeded;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\PlanLimits;

it('does not enforce limits when tenancy is disabled', function () {
    config(['short-url.tenancy.enabled' => false, 'short-url.tenancy.plans.default.links_per_month' => 0]);

    $shortUrl = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']);

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});

it('does not enforce a null (unlimited) limit', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.links_per_month' => null,
    ]);

    $shortUrl = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']);

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});

it('throws PlanLimitExceeded once the monthly link limit is reached', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.links_per_month' => 1,
    ]);

    app(ShortUrlManager::class)->create(['destination_url' => 'https://a.example']);

    expect(fn () => app(ShortUrlManager::class)->create(['destination_url' => 'https://b.example']))
        ->toThrow(PlanLimitExceeded::class);
});

it('resolves the plan via a configured plan_resolver closure', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plan_resolver' => fn ($tenantId) => 'pro',
        'short-url.tenancy.plans.pro.links_per_month' => null,
        'short-url.tenancy.plans.default.links_per_month' => 0,
    ]);

    expect(app(PlanLimits::class)->currentPlan())->toBe('pro');

    $shortUrl = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']);

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});

it('counts link usage scoped to the current tenant only', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.links_per_month' => 1,
    ]);

    ShortUrl::factory()->create(['tenant_id' => 2]);

    $shortUrl = app(ShortUrlManager::class)->create(['destination_url' => 'https://example.com']);

    expect($shortUrl)->toBeInstanceOf(ShortUrl::class);
});
