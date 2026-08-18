<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * Segment HTTP Tracking API /track call, authenticated with the write key
 * as basic-auth username (Segment's documented server-side auth scheme).
 */
class SegmentAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $writeKey = config('short-url.analytics.segment.write_key');

        if (! $writeKey) {
            return;
        }

        try {
            $anonymousId = substr((string) ($visit['ip_hash'] ?? uniqid('anon_', true)), 0, 36);

            Http::timeout(2)->withBasicAuth($writeKey, '')->post('https://api.segment.io/v1/track', [
                'anonymousId' => $anonymousId,
                'event' => 'Short URL Visited',
                'properties' => array_filter([
                    'url_key' => $visit['url_key'] ?? null,
                    'country' => $visit['country_code'] ?? null,
                    'device_type' => $visit['device_type'] ?? null,
                    'referer_type' => $visit['referer_type'] ?? null,
                ]),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
