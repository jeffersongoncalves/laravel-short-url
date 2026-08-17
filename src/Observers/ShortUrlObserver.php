<?php

namespace JeffersonGoncalves\LaravelShortUrl\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveShortUrl;

class ShortUrlObserver
{
    public function saved(ShortUrl $shortUrl): void
    {
        $this->flush($shortUrl);
    }

    public function deleted(ShortUrl $shortUrl): void
    {
        $this->flush($shortUrl);
    }

    protected function flush(ShortUrl $shortUrl): void
    {
        Cache::forget(ResolveShortUrl::cacheKey($this->resolveHost(), $shortUrl->url_key));
    }

    protected function resolveHost(): string
    {
        return config('short-url.route.domain')
            ?? parse_url((string) config('app.url'), PHP_URL_HOST)
            ?? 'localhost';
    }
}
