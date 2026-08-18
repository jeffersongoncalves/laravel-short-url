<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\MatomoAnalyticsDriver;

it('does nothing when url or site id are missing', function () {
    Http::fake();
    config(['short-url.analytics.matomo.url' => null, 'short-url.analytics.matomo.site_id' => 1]);

    (new MatomoAnalyticsDriver)->record(['url_key' => 'abc1234']);

    Http::assertNothingSent();
});

it('hits the tracking endpoint when configured', function () {
    config(['short-url.analytics.matomo.url' => 'https://matomo.example.com', 'short-url.analytics.matomo.site_id' => 3]);
    Http::fake(['*matomo.example.com*' => Http::response('', 200)]);

    (new MatomoAnalyticsDriver)->record(['url_key' => 'abc1234', 'country_code' => 'BR']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/matomo.php') && str_contains($request->url(), 'idsite=3'));
});
