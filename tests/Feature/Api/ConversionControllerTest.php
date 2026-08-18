<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\LaravelShortUrl\Events\ConversionRecorded;
use JeffersonGoncalves\LaravelShortUrl\Jobs\DispatchConversionJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

beforeEach(function () {
    config(['short-url.api.enabled' => true]);
});

it('records a conversion by url_key', function () {
    Bus::fake();
    Event::fake([ConversionRecorded::class]);
    $shortUrl = ShortUrl::factory()->create(['url_key' => 'abc1234']);

    $this->withHeaders(apiHeaders(['conversions:write']))
        ->postJson('/api/short-url/v1/conversions', [
            'url_key' => 'abc1234',
            'event_name' => 'purchase',
            'value' => 19.9,
            'currency' => 'USD',
        ])
        ->assertCreated();

    expect(Conversion::query()->where('short_url_id', $shortUrl->id)->where('event_name', 'purchase')->exists())->toBeTrue();
    Event::assertDispatched(ConversionRecorded::class);
    Bus::assertDispatched(DispatchConversionJob::class);
});

it('records a conversion by short_url_uuid', function () {
    $shortUrl = ShortUrl::factory()->create();

    $this->withHeaders(apiHeaders(['conversions:write']))
        ->postJson('/api/short-url/v1/conversions', [
            'short_url_uuid' => $shortUrl->uuid,
            'event_name' => 'signup',
        ])
        ->assertCreated();

    expect(Conversion::query()->where('short_url_id', $shortUrl->id)->exists())->toBeTrue();
});

it('rejects a conversion with neither url_key nor short_url_uuid', function () {
    $this->withHeaders(apiHeaders(['conversions:write']))
        ->postJson('/api/short-url/v1/conversions', ['event_name' => 'purchase'])
        ->assertStatus(422);
});

it('requires the conversions:write ability', function () {
    $this->withHeaders(apiHeaders(['links:read']))
        ->postJson('/api/short-url/v1/conversions', ['event_name' => 'purchase', 'url_key' => 'abc1234'])
        ->assertStatus(403);
});
