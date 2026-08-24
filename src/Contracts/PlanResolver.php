<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

/**
 * Resolves which key of short-url.tenancy.plans a tenant is on. Bind an
 * implementation in your own ServiceProvider::register() — see
 * Tenancy\PlanLimits. Without one bound, every tenant is on "default".
 *
 * A container binding rather than a config Closure so it survives
 * `php artisan config:cache` (see TenantResolver).
 */
interface PlanResolver
{
    public function resolve(int|string $tenantId): string;
}
