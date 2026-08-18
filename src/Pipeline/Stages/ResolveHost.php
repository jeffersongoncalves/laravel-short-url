<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class ResolveHost
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $context->host = config('short-url.route.domain') ?? $context->request->getHost();

        if (config('short-url.domains.enabled', false)) {
            $context->customDomain = $this->resolveCustomDomain($context->host);
        }

        return $next($context);
    }

    protected function resolveCustomDomain(string $host): ?CustomDomain
    {
        if (! config('short-url.cache.enabled', true)) {
            return CustomDomain::forHost($host);
        }

        return Cache::remember(
            config('short-url.cache.prefix', 'short_url').":domain:{$host}",
            (int) config('short-url.cache.ttl', 3600),
            fn () => CustomDomain::forHost($host)
        );
    }
}
