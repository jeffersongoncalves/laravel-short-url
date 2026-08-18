<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\WebhookDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Events\ConversionRecorded;
use JeffersonGoncalves\LaravelShortUrl\Jobs\DispatchConversionJob;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

class ConversionController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url_key' => ['required_without:short_url_uuid', 'string'],
            'short_url_uuid' => ['required_without:url_key', 'string'],
            'event_name' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $shortUrl = isset($data['short_url_uuid'])
            ? ShortUrl::query()->where('uuid', $data['short_url_uuid'])->firstOrFail()
            : ShortUrl::query()->where('url_key', $data['url_key'])->firstOrFail();

        $conversion = Conversion::query()->create([
            'short_url_id' => $shortUrl->id,
            'event_name' => $data['event_name'],
            'value' => $data['value'] ?? null,
            'currency' => $data['currency'] ?? null,
            'external_id' => $data['external_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'created_at' => now(),
        ]);

        ConversionRecorded::dispatch($conversion);
        DispatchConversionJob::dispatch($conversion->id);

        app(WebhookDispatcher::class)->dispatch('conversion.recorded', [
            'short_url_id' => $shortUrl->id,
            'url_key' => $shortUrl->url_key,
            'event_name' => $conversion->event_name,
            'value' => $conversion->value,
            'currency' => $conversion->currency,
        ], $shortUrl);

        return response()->json(['data' => $conversion], 201);
    }
}
