<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveShortUrl;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('resolves a short url by key and caches it', function () {
    $shortUrl = ShortUrl::factory()->create(['url_key' => 'cache01']);

    $context = new RedirectContext(Request::create('http://short.test/cache01'), 'cache01');
    $context->host = 'short.test';

    $result = (new ResolveShortUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->shortUrl->is($shortUrl))->toBeTrue()
        ->and(Cache::has(ResolveShortUrl::cacheKey('short.test', 'cache01')))->toBeTrue();
});

it('invalidates the cache when the short url is updated', function () {
    $shortUrl = ShortUrl::factory()->create(['url_key' => 'cache02']);

    Cache::put(ResolveShortUrl::cacheKey('short.test', 'cache02'), $shortUrl, 3600);

    $shortUrl->update(['title' => 'Updated']);

    expect(Cache::has(ResolveShortUrl::cacheKey('short.test', 'cache02')))->toBeFalse();
});

it('invalidates the cache when the short url is deleted', function () {
    $shortUrl = ShortUrl::factory()->create(['url_key' => 'cache03']);

    Cache::put(ResolveShortUrl::cacheKey('short.test', 'cache03'), $shortUrl, 3600);

    $shortUrl->delete();

    expect(Cache::has(ResolveShortUrl::cacheKey('short.test', 'cache03')))->toBeFalse();
});

it('aborts with 404 when no short url matches the key', function () {
    $context = new RedirectContext(Request::create('http://short.test/missing'), 'missing');
    $context->host = 'short.test';

    (new ResolveShortUrl)($context, fn (RedirectContext $c) => $c);
})->throws(NotFoundHttpException::class);
