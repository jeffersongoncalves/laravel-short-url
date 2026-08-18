<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * Mixpanel /track endpoint (JSON body, ip=0 so Mixpanel doesn't geolocate
 * from our server's own IP).
 */
class MixpanelAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $token = config('short-url.analytics.mixpanel.token');

        if (! $token) {
            return;
        }

        try {
            $distinctId = substr((string) ($visit['ip_hash'] ?? uniqid('anon_', true)), 0, 36);

            Http::timeout(2)->post('https://api.mixpanel.com/track?ip=0', [[
                'event' => 'short_url_visit',
                'properties' => array_filter([
                    'token' => $token,
                    'distinct_id' => $distinctId,
                    'url_key' => $visit['url_key'] ?? null,
                    'country' => $visit['country_code'] ?? null,
                    'device_type' => $visit['device_type'] ?? null,
                    'referer_type' => $visit['referer_type'] ?? null,
                ]),
            ]]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
