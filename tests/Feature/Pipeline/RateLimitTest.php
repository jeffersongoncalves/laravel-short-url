<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages\RateLimit;

beforeEach(function () {
    RateLimiter::clear('short-url:203.0.113.1');
});

it('passes through when rate limiting is disabled', function () {
    config(['short-url.security.rate_limit.enabled' => false]);

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');
    $result = (new RateLimit)($context, fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});

it('passes through under the attempt limit', function () {
    config(['short-url.security.rate_limit.enabled' => true, 'short-url.security.rate_limit.max_attempts' => 5]);

    $context = new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');
    $result = (new RateLimit)($context, fn (RedirectContext $c) => $c);

    expect($result)->toBeInstanceOf(RedirectContext::class);
});

it('returns 429 with a Retry-After header once the limit is exceeded', function () {
    config(['short-url.security.rate_limit.enabled' => true, 'short-url.security.rate_limit.max_attempts' => 1]);

    $context = fn () => new RedirectContext(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.1']), 'abc1234');

    (new RateLimit)($context(), fn (RedirectContext $c) => $c);
    $response = (new RateLimit)($context(), fn (RedirectContext $c) => $c);

    expect($response->getStatusCode())->toBe(429)
        ->and($response->headers->has('Retry-After'))->toBeTrue();
});
