<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * Umami's /api/send event collector.
 */
class UmamiAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $host = config('short-url.analytics.umami.host');
        $websiteId = config('short-url.analytics.umami.website_id');

        if (! $host || ! $websiteId) {
            return;
        }

        try {
            Http::timeout(2)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(rtrim((string) $host, '/').'/api/send', [
                    'type' => 'event',
                    'payload' => array_filter([
                        'website' => $websiteId,
                        'url' => isset($visit['url_key']) ? '/'.$visit['url_key'] : null,
                        'referrer' => $visit['referer_url'] ?? null,
                        'hostname' => $visit['referer_host'] ?? null,
                    ]),
                ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
