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
