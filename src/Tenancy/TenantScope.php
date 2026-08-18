<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * A no-op unless short-url.tenancy.enabled is on and a current tenant
 * resolves (see TenantContext) — checked at query time, not at scope
 * registration, so toggling the config takes effect immediately without
 * re-registering scopes.
 *
 * Illuminate\Database\Eloquent\Scope only became generic in Laravel 13;
 * on Laravel 12 this @implements tag itself is a PHPStan error ("interface
 * is not generic"). Both directions are baselined in phpstan-baseline.neon
 * since the package supports both majors and can't satisfy both at once.
 *
 * @implements Scope<Model>
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->currentId();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
