<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\PlausibleAnalyticsDriver;

it('does nothing when no domain is configured', function () {
    Http::fake();
    config(['short-url.analytics.plausible.domain' => null]);

    (new PlausibleAnalyticsDriver)->record(['url_key' => 'abc1234']);

    Http::assertNothingSent();
});

it('posts a pageview event when configured', function () {
    config(['short-url.analytics.plausible.domain' => 'links.example.com']);
    Http::fake(['*plausible.io*' => Http::response('', 202)]);

    (new PlausibleAnalyticsDriver)->record(['url_key' => 'abc1234']);

    Http::assertSent(fn ($request) => $request['domain'] === 'links.example.com');
});
