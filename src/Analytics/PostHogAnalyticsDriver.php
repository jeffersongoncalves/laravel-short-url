<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * PostHog capture API. distinct_id is derived from ip_hash the same way
 * Ga4AnalyticsDriver derives client_id, so repeat (hashed) visitors land
 * under one PostHog person without ever sending a real cookie/IP.
 */
class PostHogAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $apiKey = config('short-url.analytics.posthog.api_key');

        if (! $apiKey) {
            return;
        }

        try {
            $host = rtrim((string) config('short-url.analytics.posthog.host', 'https://us.i.posthog.com'), '/');
            $distinctId = substr((string) ($visit['ip_hash'] ?? uniqid('anon_', true)), 0, 36);

            Http::timeout(2)->post("{$host}/capture/", [
                'api_key' => $apiKey,
                'event' => 'short_url_visit',
                'distinct_id' => $distinctId,
                'properties' => array_filter([
                    '$current_url' => $visit['referer_url'] ?? null,
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
