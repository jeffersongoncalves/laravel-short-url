<?php

namespace JeffersonGoncalves\LaravelShortUrl\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\PlanLimits;

class CustomDomainObserver
{
    public function creating(CustomDomain $domain): void
    {
        app(PlanLimits::class)->assertCanCreateDomain();
    }

    /**
     * At most one default domain per tenant — marking one default unsets
     * every other default the same tenant has.
     */
    public function saving(CustomDomain $domain): void
    {
        if (! $domain->is_default || ! $domain->isDirty('is_default')) {
            return;
        }

        CustomDomain::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $domain->tenant_id)
            ->when($domain->exists, fn ($query) => $query->whereKeyNot($domain->getKey()))
            ->update(['is_default' => false]);
    }

    public function saved(CustomDomain $domain): void
    {
        $this->flush($domain);
    }

    public function deleted(CustomDomain $domain): void
    {
        $this->flush($domain);
    }

    protected function flush(CustomDomain $domain): void
    {
        Cache::forget(config('short-url.cache.prefix', 'short_url').":domain:{$domain->domain}");

        $original = $domain->getOriginal('domain');

        if ($original && $original !== $domain->domain) {
            Cache::forget(config('short-url.cache.prefix', 'short_url').":domain:{$original}");
        }
    }
}
