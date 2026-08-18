<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Conversions\TikTokCapiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

it('does nothing when pixel code or access token are missing', function () {
    Http::fake();
    config(['short-url.conversions.tiktok.pixel_code' => null]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new TikTokCapiDispatcher)->send($conversion);

    Http::assertNothingSent();
});

it('sends the conversion to the tiktok events api when configured', function () {
    config(['short-url.conversions.tiktok.pixel_code' => 'pixel-1', 'short-url.conversions.tiktok.access_token' => 'token']);
    Http::fake(['*business-api.tiktok.com*' => Http::response(['code' => 0])]);

    $conversion = new Conversion(['event_name' => 'purchase', 'value' => 10, 'currency' => 'USD', 'occurred_at' => now()]);
    (new TikTokCapiDispatcher)->send($conversion);

    Http::assertSent(fn ($request) => $request->hasHeader('Access-Token', 'token') && $request['event_source_id'] === 'pixel-1');
});

it('never throws when the api call fails', function () {
    config(['short-url.conversions.tiktok.pixel_code' => 'pixel-1', 'short-url.conversions.tiktok.access_token' => 'token']);
    Http::fake(['*business-api.tiktok.com*' => Http::response([], 500)]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new TikTokCapiDispatcher)->send($conversion);
})->throwsNoExceptions();
