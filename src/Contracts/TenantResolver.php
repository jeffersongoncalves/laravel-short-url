<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

/**
 * Resolves "the current tenant id" for a host app with its own tenancy
 * (not stancl/tenancy). Bind an implementation in your own
 * ServiceProvider::register() — see Tenancy\TenantContext.
 *
 * A plain config Closure can't be used for this: `php artisan
 * config:cache` var_export()s the config array, which throws on a
 * Closure. A container binding lives in code, not the cached config, so
 * it's unaffected.
 */
interface TenantResolver
{
    public function resolve(): int|string|null;
}
