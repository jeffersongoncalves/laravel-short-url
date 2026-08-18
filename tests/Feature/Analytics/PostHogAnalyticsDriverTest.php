<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\PostHogAnalyticsDriver;

it('does nothing when no api key is configured', function () {
    Http::fake();
    config(['short-url.analytics.posthog.api_key' => null]);

    (new PostHogAnalyticsDriver)->record(['ip_hash' => 'abc']);

    Http::assertNothingSent();
});

it('posts a capture event when configured', function () {
    config(['short-url.analytics.posthog.api_key' => 'phc_test']);
    Http::fake(['*i.posthog.com*' => Http::response(['status' => 1])]);

    (new PostHogAnalyticsDriver)->record(['ip_hash' => 'abc', 'country_code' => 'BR']);

    Http::assertSent(fn ($request) => $request['api_key'] === 'phc_test' && str_contains($request->url(), '/capture/'));
});
