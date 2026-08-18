<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\CheckAvailability;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function checkAvailabilityContext(ShortUrl $shortUrl): RedirectContext
{
    $context = new RedirectContext(Request::create('/'), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    return $context;
}

it('passes through an enabled short url with visits left', function () {
    $shortUrl = ShortUrl::factory()->create(['is_enabled' => true]);

    $result = (new CheckAvailability)(checkAvailabilityContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($result->shortUrl->is($shortUrl))->toBeTrue();
});

it('blocks a disabled short url', function () {
    $shortUrl = ShortUrl::factory()->create(['is_enabled' => false]);

    (new CheckAvailability)(checkAvailabilityContext($shortUrl), fn (RedirectContext $c) => $c);
})->throws(NotFoundHttpException::class);

it('renders the branded expired page when there is no fallback redirect', function () {
    $shortUrl = ShortUrl::factory()->create(['expires_at' => now()->subDay()]);

    $response = (new CheckAvailability)(checkAvailabilityContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($response->getStatusCode())->toBe(410);
});

it('redirects to the fallback url when expired with expiration_redirect_url set', function () {
    $shortUrl = ShortUrl::factory()->create([
        'expires_at' => now()->subDay(),
        'expiration_redirect_url' => 'https://example.com/expired',
    ]);

    $response = (new CheckAvailability)(checkAvailabilityContext($shortUrl), fn (RedirectContext $c) => $c);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('https://example.com/expired');
});

it('blocks a short url that reached its max visits', function () {
    $shortUrl = ShortUrl::factory()->create(['max_visits' => 5, 'total_visits' => 5]);

    (new CheckAvailability)(checkAvailabilityContext($shortUrl), fn (RedirectContext $c) => $c);
})->throws(NotFoundHttpException::class);
