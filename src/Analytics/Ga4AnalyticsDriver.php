<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * GA4 Measurement Protocol. client_id is derived deterministically from
 * the visit's ip_hash so repeat visits from the same (hashed) visitor
 * land under one GA4 client without ever sending a real cookie/IP.
 */
class Ga4AnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $measurementId = config('short-url.analytics.ga4.measurement_id');
        $apiSecret = config('short-url.analytics.ga4.api_secret');

        if (! $measurementId || ! $apiSecret) {
            return;
        }

        $this->send($measurementId, $apiSecret, $visit);
    }

    /**
     * @param  array<string, mixed>  $visit
     */
    protected function send(string $measurementId, string $apiSecret, array $visit): void
    {
        try {
            $clientId = substr((string) ($visit['ip_hash'] ?? uniqid('anon_', true)), 0, 36);

            Http::timeout(2)->post(
                "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}",
                [
                    'client_id' => $clientId,
                    'events' => [[
                        'name' => 'short_url_visit',
                        'params' => array_filter([
                            'country' => $visit['country_code'] ?? null,
                            'device_type' => $visit['device_type'] ?? null,
                            'referer_type' => $visit['referer_type'] ?? null,
                        ]),
                    ]],
                ]
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
