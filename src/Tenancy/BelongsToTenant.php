<?php

namespace JeffersonGoncalves\LaravelShortUrl\Tenancy;

/**
 * Applied to every model carrying a tenant_id column. Auto-fills
 * tenant_id from the current tenant on create and scopes every query to
 * it — both no-ops when multi-tenancy is off (see TenantContext).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            if ($model->tenant_id === null) {
                $model->tenant_id = app(TenantContext::class)->currentId();
            }
        });
    }
}
