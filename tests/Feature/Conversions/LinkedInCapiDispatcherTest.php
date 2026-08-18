<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Conversions\LinkedInCapiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

it('does nothing when access token or conversion id are missing', function () {
    Http::fake();
    config(['short-url.conversions.linkedin.access_token' => null]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new LinkedInCapiDispatcher)->send($conversion);

    Http::assertNothingSent();
});

it('sends the conversion to the linkedin conversions api when configured', function () {
    config([
        'short-url.conversions.linkedin.access_token' => 'token',
        'short-url.conversions.linkedin.conversion_id' => 'urn:lla:llaPartnerConversion:123',
    ]);
    Http::fake(['*api.linkedin.com*' => Http::response('', 201)]);

    $conversion = new Conversion(['event_name' => 'purchase', 'value' => 10, 'currency' => 'USD', 'occurred_at' => now()]);
    (new LinkedInCapiDispatcher)->send($conversion);

    Http::assertSent(fn ($request) => $request['conversion'] === 'urn:lla:llaPartnerConversion:123');
});

it('never throws when the api call fails', function () {
    config([
        'short-url.conversions.linkedin.access_token' => 'token',
        'short-url.conversions.linkedin.conversion_id' => 'urn:lla:llaPartnerConversion:123',
    ]);
    Http::fake(['*api.linkedin.com*' => Http::response([], 500)]);

    $conversion = new Conversion(['event_name' => 'purchase', 'occurred_at' => now()]);
    (new LinkedInCapiDispatcher)->send($conversion);
})->throwsNoExceptions();
