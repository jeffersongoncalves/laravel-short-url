<?php

use JeffersonGoncalves\LaravelShortUrl\Tenancy\TenantContext;

it('returns null when tenancy is disabled', function () {
    config(['short-url.tenancy.enabled' => false, 'short-url.tenancy.current_tenant_id' => 42]);

    expect((new TenantContext)->currentId())->toBeNull();
});

it('returns null when tenancy is enabled but no tenant is set', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => null]);

    expect((new TenantContext)->currentId())->toBeNull();
});

it('resolves the current_tenant_id override when tenancy is enabled', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 42]);

    expect((new TenantContext)->currentId())->toBe(42);
});
