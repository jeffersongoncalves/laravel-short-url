<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\BuildFinalUrl;

it('appends the query string when forwarding is enabled', function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page',
        'forward_query_params' => true,
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x?utm_source=test&ref=abc'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page?ref=abc&utm_source=test');
});

it('does not append the query string when forwarding is disabled', function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page',
        'forward_query_params' => false,
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x?utm_source=test'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page');
});

it('appends with an ampersand when the destination already has a query string', function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page?foo=bar',
        'forward_query_params' => true,
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x?utm_source=test'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page?foo=bar&utm_source=test');
});
