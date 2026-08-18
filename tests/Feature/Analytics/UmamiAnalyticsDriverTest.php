<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\UmamiAnalyticsDriver;

it('does nothing when host or website id are missing', function () {
    Http::fake();
    config(['short-url.analytics.umami.host' => null, 'short-url.analytics.umami.website_id' => 'site-1']);

    (new UmamiAnalyticsDriver)->record(['url_key' => 'abc1234']);

    Http::assertNothingSent();
});

it('posts an event to /api/send when configured', function () {
    config(['short-url.analytics.umami.host' => 'https://umami.example.com', 'short-url.analytics.umami.website_id' => 'site-1']);
    Http::fake(['*umami.example.com*' => Http::response('', 200)]);

    (new UmamiAnalyticsDriver)->record(['url_key' => 'abc1234']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/send') && $request['payload']['website'] === 'site-1');
});
