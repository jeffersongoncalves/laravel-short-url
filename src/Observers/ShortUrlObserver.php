<?php

namespace JeffersonGoncalves\LaravelShortUrl\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SafeBrowsingChecker;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\UnsafeDestinationException;
use JeffersonGoncalves\LaravelShortUrl\Jobs\CheckSafeBrowsingJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Security\DestinationUrlCollector;

class ShortUrlObserver
{
    public function saving(ShortUrl $shortUrl): void
    {
        if (! $this->safeBrowsingScanNeeded($shortUrl)) {
            return;
        }

        if (config('short-url.security.safe_browsing.mode', 'sync') !== 'sync') {
            return;
        }

        $checker = app(SafeBrowsingChecker::class);
        $status = 'safe';

        foreach (DestinationUrlCollector::collect($shortUrl) as $url) {
            $result = $checker->check($url);

            if ($result->status === 'unsafe') {
                throw new UnsafeDestinationException($url, $result->threats);
            }

            if ($result->status === 'unknown') {
                $status = 'unknown';
            }
        }

        $shortUrl->safe_browsing_status = $status;
        $shortUrl->safe_browsing_checked_at = now();
    }

    public function saved(ShortUrl $shortUrl): void
    {
        $this->flush($shortUrl);
        $this->dispatchAsyncSafeBrowsingScan($shortUrl);
    }

    public function deleted(ShortUrl $shortUrl): void
    {
        $this->flush($shortUrl);
    }

    protected function flush(ShortUrl $shortUrl): void
    {
        Cache::forget(ResolveShortUrl::cacheKey($this->resolveHost(), $shortUrl->url_key));

        if ($shortUrl->custom_domain_id) {
            $domain = CustomDomain::query()->find($shortUrl->custom_domain_id)?->domain;

            if ($domain) {
                Cache::forget(ResolveShortUrl::cacheKey($domain, $shortUrl->url_key));
            }
        }
    }

    protected function resolveHost(): string
    {
        return config('short-url.route.domain')
            ?? parse_url((string) config('app.url'), PHP_URL_HOST)
            ?? 'localhost';
    }

    protected function safeBrowsingScanNeeded(ShortUrl $shortUrl): bool
    {
        if (! config('short-url.security.safe_browsing.enabled', false)
            || config('short-url.security.safe_browsing.bypass', false)) {
            return false;
        }

        return $shortUrl->isDirty(['destination_url', 'rotation_variants', 'targeting_rules']);
    }

    protected function dispatchAsyncSafeBrowsingScan(ShortUrl $shortUrl): void
    {
        if (! config('short-url.security.safe_browsing.enabled', false)
            || config('short-url.security.safe_browsing.bypass', false)
            || config('short-url.security.safe_browsing.mode', 'sync') !== 'async') {
            return;
        }

        // wasChanged() is only populated by Eloquent on UPDATE, never on the
        // initial INSERT — wasRecentlyCreated covers that case explicitly.
        if (! $shortUrl->wasRecentlyCreated && ! $shortUrl->wasChanged(['destination_url', 'rotation_variants', 'targeting_rules'])) {
            return;
        }

        CheckSafeBrowsingJob::dispatch($shortUrl->id);
    }
}
