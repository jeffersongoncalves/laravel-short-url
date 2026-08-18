<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Conversions\MetaCapiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

it('does nothing when pixel id or access token are missing', function () {
    Http::fake();
    config(['short-url.conversions.meta.pixel_id' => null]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new MetaCapiDispatcher)->send($conversion);

    Http::assertNothingSent();
});

it('sends the conversion to the meta graph api when configured', function () {
    config(['short-url.conversions.meta.pixel_id' => '123', 'short-url.conversions.meta.access_token' => 'token']);
    Http::fake(['*graph.facebook.com*' => Http::response(['events_received' => 1])]);

    $conversion = new Conversion(['event_name' => 'purchase', 'value' => 10, 'currency' => 'USD', 'occurred_at' => now()]);
    (new MetaCapiDispatcher)->send($conversion);

    Http::assertSent(fn ($request) => str_contains($request->url(), '123/events'));
});

it('never throws when the api call fails', function () {
    config(['short-url.conversions.meta.pixel_id' => '123', 'short-url.conversions.meta.access_token' => 'token']);
    Http::fake(['*graph.facebook.com*' => Http::response([], 500)]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new MetaCapiDispatcher)->send($conversion);
})->throwsNoExceptions();
