<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\MixpanelAnalyticsDriver;

it('does nothing when no token is configured', function () {
    Http::fake();
    config(['short-url.analytics.mixpanel.token' => null]);

    (new MixpanelAnalyticsDriver)->record(['ip_hash' => 'abc']);

    Http::assertNothingSent();
});

it('posts a track event when configured', function () {
    config(['short-url.analytics.mixpanel.token' => 'mp_test']);
    Http::fake(['*api.mixpanel.com*' => Http::response('1')]);

    (new MixpanelAnalyticsDriver)->record(['ip_hash' => 'abc', 'country_code' => 'BR']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.mixpanel.com/track') && $request[0]['properties']['token'] === 'mp_test');
});
