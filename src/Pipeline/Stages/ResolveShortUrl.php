<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveShortUrl
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = config('short-url.cache.enabled', true)
            ? Cache::remember(
                static::cacheKey($context->host ?? '', $context->urlKey),
                (int) config('short-url.cache.ttl', 3600),
                fn () => $this->find($context->urlKey)
            )
            : $this->find($context->urlKey);

        if (! $shortUrl) {
            throw new NotFoundHttpException;
        }

        $context->shortUrl = $shortUrl;

        return $next($context);
    }

    protected function find(string $urlKey): ?ShortUrl
    {
        return ShortUrl::findByKey($urlKey);
    }

    public static function cacheKey(string $host, string $urlKey): string
    {
        return config('short-url.cache.prefix', 'short_url').":{$host}:{$urlKey}";
    }
}
