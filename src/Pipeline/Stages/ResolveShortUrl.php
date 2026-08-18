<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveShortUrl
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        if ($context->urlKey === '') {
            return $this->resolveDomainRoot($context);
        }

        $customDomainId = $context->customDomain?->id;

        $shortUrl = config('short-url.cache.enabled', true)
            ? Cache::remember(
                static::cacheKey($context->host ?? '', $context->urlKey),
                (int) config('short-url.cache.ttl', 3600),
                fn () => $this->find($context->urlKey, $customDomainId)
            )
            : $this->find($context->urlKey, $customDomainId);

        if (! $shortUrl) {
            throw new NotFoundHttpException;
        }

        $context->shortUrl = $shortUrl;

        return $next($context);
    }

    /**
     * A request with no url key at all only ever reaches this stage on a
     * verified custom domain (via the package's fallback route). Redirects
     * to that domain's configured root, or 404 if it has none.
     */
    protected function resolveDomainRoot(RedirectContext $context): mixed
    {
        $rootRedirectUrl = $context->customDomain?->root_redirect_url;

        if (! $rootRedirectUrl) {
            throw new NotFoundHttpException;
        }

        return new RedirectResponse($rootRedirectUrl);
    }

    protected function find(string $urlKey, ?int $customDomainId): ?ShortUrl
    {
        return ShortUrl::findByKey($urlKey, $customDomainId);
    }

    public static function cacheKey(string $host, string $urlKey): string
    {
        return config('short-url.cache.prefix', 'short_url').":{$host}:{$urlKey}";
    }
}
