<?php

use JeffersonGoncalves\LaravelShortUrl\Exceptions\PlanLimitExceeded;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

it('throws PlanLimitExceeded once the domain limit is reached', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.domains' => 1,
    ]);

    CustomDomain::factory()->create();

    expect(fn () => CustomDomain::factory()->create())->toThrow(PlanLimitExceeded::class);
});

it('does not enforce a null domain limit', function () {
    config([
        'short-url.tenancy.enabled' => true,
        'short-url.tenancy.current_tenant_id' => 1,
        'short-url.tenancy.plans.default.domains' => null,
    ]);

    CustomDomain::factory()->create();
    $domain = CustomDomain::factory()->create();

    expect($domain)->toBeInstanceOf(CustomDomain::class);
});
