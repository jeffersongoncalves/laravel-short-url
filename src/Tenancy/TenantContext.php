<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tenancy;

/**
 * Resolves "the current tenant id", if any. Multi-tenancy is a pure
 * feature-flag: with short-url.tenancy.enabled off (the default) this
 * always returns null and nothing in the package behaves differently.
 *
 * With it on, the tenant id comes from — in order:
 * 1. stancl/tenancy's tenant() helper, when the package is installed and
 *    a tenant is currently initialized (never a hard dependency: guarded
 *    by function_exists so the package still boots without it).
 * 2. short-url.tenancy.current_tenant_id, a plain config override for
 *    hosts that manage tenancy their own way (or for tests).
 */
class TenantContext
{
    public function currentId(): int|string|null
    {
        if (! config('short-url.tenancy.enabled', false)) {
            return null;
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
