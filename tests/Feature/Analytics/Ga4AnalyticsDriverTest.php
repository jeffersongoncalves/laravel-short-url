<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\Ga4AnalyticsDriver;

it('does nothing when not configured', function () {
    Http::fake();
    config(['short-url.analytics.ga4.measurement_id' => null]);

    (new Ga4AnalyticsDriver)->record(['ip_hash' => 'abc']);

    Http::assertNothingSent();
});

it('posts a measurement protocol event when configured', function () {
    config(['short-url.analytics.ga4.measurement_id' => 'G-TEST', 'short-url.analytics.ga4.api_secret' => 'secret']);
    Http::fake(['*google-analytics.com*' => Http::response('', 204)]);

    (new Ga4AnalyticsDriver)->record(['ip_hash' => 'abc', 'country_code' => 'BR']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'measurement_id=G-TEST'));
});
