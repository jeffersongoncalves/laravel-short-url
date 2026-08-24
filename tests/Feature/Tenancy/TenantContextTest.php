<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\TenantResolver;
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

it('resolves the current tenant through a bound TenantResolver', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 42]);

    app()->bind(TenantResolver::class, fn () => new class implements TenantResolver
    {
        public function resolve(): int|string|null
        {
            return 'acme';
        }
    });

    expect((new TenantContext)->currentId())->toBe('acme');
});
