<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tenancy;

use JeffersonGoncalves\LaravelShortUrl\Contracts\TenantResolver;

/**
 * Resolves "the current tenant id", if any. Multi-tenancy is a pure
 * feature-flag: with short-url.tenancy.enabled off (the default) this
 * always returns null and nothing in the package behaves differently.
 *
 * With it on, the tenant id comes from — in order:
 * 1. Contracts\TenantResolver, when a host app binds its own
 *    implementation (in a ServiceProvider::register()) to resolve the
 *    tenant from its own tenant model/scope instead of stancl/tenancy.
 * 2. stancl/tenancy's tenant() helper, when the package is installed and
 *    a tenant is currently initialized (never a hard dependency: guarded
 *    by function_exists so the package still boots without it).
 * 3. short-url.tenancy.current_tenant_id, a plain config override for
 *    simpler cases (or tests) that don't need a custom resolver.
 */
class TenantContext
{
    public function currentId(): int|string|null
    {
        if (! config('short-url.tenancy.enabled', false)) {
            return null;
        }

        if (app()->bound(TenantResolver::class)) {
            return app(TenantResolver::class)->resolve();
        }

        if (function_exists('tenant')) {
            $tenant = tenant();

            if ($tenant) {
                return $tenant->getKey();
            }
        }

        return config('short-url.tenancy.current_tenant_id');
    }
}
