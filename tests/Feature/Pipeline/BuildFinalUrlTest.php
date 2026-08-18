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

it("appends the link's own utm values even with no incoming query string", function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page',
        'forward_query_params' => true,
        'utm_medium' => 'sms',
        'utm_campaign' => 'spring-sale',
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page?utm_medium=sms&utm_campaign=spring-sale');
});

it("the link's own utm_medium overrides whatever the incoming click carries", function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page',
        'forward_query_params' => true,
        'utm_medium' => 'email',
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x?utm_medium=whatever-a-client-injected'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page?utm_medium=email');
});

it('strips incoming utm params from the destination when strip_utm_from_destination is on', function () {
    $shortUrl = ShortUrl::factory()->make([
        'destination_url' => 'https://example.com/page',
        'forward_query_params' => true,
        'strip_utm_from_destination' => true,
        'utm_medium' => 'agent',
    ]);
    $context = new RedirectContext(Request::create('http://short.test/x?utm_source=random&ref=abc'), 'x');
    $context->shortUrl = $shortUrl;

    $result = (new BuildFinalUrl)($context, fn (RedirectContext $c) => $c);

    expect($result->finalUrl)->toBe('https://example.com/page?ref=abc&utm_medium=agent');
});
