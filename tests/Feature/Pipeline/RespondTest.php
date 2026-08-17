<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\Respond;

it('builds a redirect response using the configured status code', function () {
    $shortUrl = ShortUrl::factory()->make(['redirect_status_code' => 301]);
    $context = new RedirectContext(Request::create('/'), 'x');
    $context->shortUrl = $shortUrl;
    $context->finalUrl = 'https://example.com';

    $result = (new Respond)($context, fn (RedirectContext $c) => $c);

    expect($result->response->getStatusCode())->toBe(301)
        ->and($result->response->getTargetUrl())->toBe('https://example.com');
});
