<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Analytics\SegmentAnalyticsDriver;

it('does nothing when no write key is configured', function () {
    Http::fake();
    config(['short-url.analytics.segment.write_key' => null]);

    (new SegmentAnalyticsDriver)->record(['ip_hash' => 'abc']);

    Http::assertNothingSent();
});

it('posts a track event when configured', function () {
    config(['short-url.analytics.segment.write_key' => 'wk_test']);
    Http::fake(['*api.segment.io*' => Http::response('', 200)]);

    (new SegmentAnalyticsDriver)->record(['ip_hash' => 'abc', 'country_code' => 'BR']);

    Http::assertSent(fn ($request) => $request['event'] === 'Short URL Visited');
});
