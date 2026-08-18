<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Conversions\GoogleEnhancedConversionsDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

it('does nothing when required config is missing', function () {
    Http::fake();
    config(['short-url.conversions.google.customer_id' => null]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new GoogleEnhancedConversionsDispatcher)->send($conversion);

    Http::assertNothingSent();
});

it('uploads the click conversion when configured', function () {
    config([
        'short-url.conversions.google.customer_id' => '123-456-7890',
        'short-url.conversions.google.developer_token' => 'dev-token',
        'short-url.conversions.google.access_token' => 'access-token',
        'short-url.conversions.google.conversion_action_id' => '999',
    ]);
    Http::fake(['*googleads.googleapis.com*' => Http::response(['results' => []])]);

    $conversion = new Conversion(['event_name' => 'purchase', 'value' => 10, 'currency' => 'USD', 'occurred_at' => now()]);
    (new GoogleEnhancedConversionsDispatcher)->send($conversion);

    Http::assertSent(fn ($request) => str_contains($request->url(), '123-456-7890:uploadClickConversions')
        && $request->hasHeader('developer-token', 'dev-token'));
});

it('never throws when the api call fails', function () {
    config([
        'short-url.conversions.google.customer_id' => '123-456-7890',
        'short-url.conversions.google.developer_token' => 'dev-token',
        'short-url.conversions.google.access_token' => 'access-token',
        'short-url.conversions.google.conversion_action_id' => '999',
    ]);
    Http::fake(['*googleads.googleapis.com*' => Http::response([], 500)]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new GoogleEnhancedConversionsDispatcher)->send($conversion);
})->throwsNoExceptions();
