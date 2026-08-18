<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveHost;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ResolveShortUrl;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    config(['short-url.domains.enabled' => true]);
    // Custom domains are inherently dynamic — the fixed test-suite default
    // (short-url.route.domain = "short.test") would override every host.
    config(['short-url.route.domain' => null]);
});

function runHostAndResolve(string $host, string $urlKey): RedirectContext
{
    $request = Request::create("http://{$host}/{$urlKey}");
    $context = new RedirectContext($request, $urlKey);

    (new ResolveHost)($context, function (RedirectContext $c) {
        (new ResolveShortUrl)($c, fn (RedirectContext $cc) => $cc);

        return $c;
    });

    return $context;
}

it('resolves a short url scoped to its verified custom domain', function () {
    $domain = CustomDomain::factory()->verified()->create(['domain' => 'links.test']);
    ShortUrl::factory()->create(['url_key' => 'abc1234', 'custom_domain_id' => $domain->id]);

    $context = runHostAndResolve('links.test', 'abc1234');

    expect($context->shortUrl)->not->toBeNull()
        ->and($context->shortUrl->custom_domain_id)->toBe($domain->id);
});

it('does not leak a domain-scoped key to the root app host', function () {
    $domain = CustomDomain::factory()->verified()->create(['domain' => 'links.test']);
    ShortUrl::factory()->create(['url_key' => 'abc1234', 'custom_domain_id' => $domain->id]);

    $request = Request::create('http://app.test/abc1234');
    $context = new RedirectContext($request, 'abc1234');

    expect(fn () => (new ResolveHost)($context, function (RedirectContext $c) {
        (new ResolveShortUrl)($c, fn (RedirectContext $cc) => $cc);

        return $c;
    }))->toThrow(NotFoundHttpException::class);
});

it('redirects a custom domain root request when root_redirect_url is set', function () {
    CustomDomain::factory()->verified()->create(['domain' => 'links.test', 'root_redirect_url' => 'https://example.com/landing']);

    $request = Request::create('http://links.test/');
    $context = new RedirectContext($request, '');

    $response = null;
    (new ResolveHost)($context, function (RedirectContext $c) use (&$response) {
        $response = (new ResolveShortUrl)($c, fn (RedirectContext $cc) => $cc);

        return $c;
    });

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('https://example.com/landing');
});

it('404s a custom domain root request with no root_redirect_url configured', function () {
    CustomDomain::factory()->verified()->create(['domain' => 'links.test']);

    $request = Request::create('http://links.test/');
    $context = new RedirectContext($request, '');

    expect(fn () => (new ResolveHost)($context, function (RedirectContext $c) {
        (new ResolveShortUrl)($c, fn (RedirectContext $cc) => $cc);

        return $c;
    }))->toThrow(NotFoundHttpException::class);
});
