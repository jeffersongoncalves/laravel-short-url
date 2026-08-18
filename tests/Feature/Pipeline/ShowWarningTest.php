<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\ShowWarning;
use JeffersonGoncalves\LaravelShortUrl\Support\WarningToken;

it('passes through when show_warning_page is disabled', function () {
    $shortUrl = ShortUrl::factory()->create(['show_warning_page' => false]);
    $context = new RedirectContext(Request::create('/'.$shortUrl->url_key), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    $result = (new ShowWarning)($context, fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});

it('renders the warning page with a signed continue link when there is no valid token', function () {
    $shortUrl = ShortUrl::factory()->create(['show_warning_page' => true, 'destination_url' => 'https://example.com/target']);
    $context = new RedirectContext(Request::create('/'.$shortUrl->url_key), $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    $response = (new ShowWarning)($context, fn (RedirectContext $c) => $c);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('_swt=')
        ->and($response->getContent())->toContain('example.com/target');
});

it('passes through when the request carries a valid warning token', function () {
    $shortUrl = ShortUrl::factory()->create(['show_warning_page' => true]);
    $token = WarningToken::generate($shortUrl->url_key);

    $request = Request::create('/'.$shortUrl->url_key, 'GET', ['_swt' => $token]);
    $context = new RedirectContext($request, $shortUrl->url_key);
    $context->shortUrl = $shortUrl;

    $result = (new ShowWarning)($context, fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});
