<?php

namespace JeffersonGoncalves\LaravelShortUrl\Conversions;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use Throwable;

/**
 * TikTok Events API (server-side).
 */
class TikTokCapiDispatcher implements ConversionApiDispatcher
{
    public function send(Conversion $conversion): void
    {
        $pixelCode = config('short-url.conversions.tiktok.pixel_code');
        $accessToken = config('short-url.conversions.tiktok.access_token');

        if (! $pixelCode || ! $accessToken) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders(['Access-Token' => $accessToken])
                ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', [
                    'event_source' => 'web',
                    'event_source_id' => $pixelCode,
                    'data' => [[
                        'event' => $conversion->event_name,
                        'event_time' => $conversion->occurred_at->timestamp,
                        'event_id' => $conversion->external_id,
                        'properties' => array_filter([
                            'value' => $conversion->value,
                            'currency' => $conversion->currency,
                        ]),
                    ]],
                ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
