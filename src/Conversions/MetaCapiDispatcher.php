<?php

namespace JeffersonGoncalves\LaravelShortUrl\Conversions;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use Throwable;

/**
 * Meta Conversions API (server-side). Requires a pixel ID and access token;
 * missing config or any request failure is swallowed — a failed S2S ping
 * must never surface as an error to the caller that recorded the conversion.
 */
class MetaCapiDispatcher implements ConversionApiDispatcher
{
    public function send(Conversion $conversion): void
    {
        $pixelId = config('short-url.conversions.meta.pixel_id');
        $accessToken = config('short-url.conversions.meta.access_token');

        if (! $pixelId || ! $accessToken) {
            return;
        }

        try {
            Http::timeout(3)->post("https://graph.facebook.com/v19.0/{$pixelId}/events", [
                'access_token' => $accessToken,
                'data' => [[
                    'event_name' => $conversion->event_name,
                    'event_time' => $conversion->occurred_at->timestamp,
                    'action_source' => 'website',
                    'custom_data' => array_filter([
                        'value' => $conversion->value,
                        'currency' => $conversion->currency,
                    ]),
                    'event_id' => $conversion->external_id,
                ]],
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
