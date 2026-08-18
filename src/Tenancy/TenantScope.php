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
