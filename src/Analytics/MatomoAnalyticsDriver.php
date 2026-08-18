<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

/**
 * Matomo Tracking HTTP API (matomo.php).
 */
class MatomoAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $url = config('short-url.analytics.matomo.url');
        $siteId = config('short-url.analytics.matomo.site_id');

        if (! $url || ! $siteId) {
            return;
        }

        try {
            Http::timeout(2)->get(rtrim((string) $url, '/').'/matomo.php', array_filter([
                'idsite' => $siteId,
                'rec' => 1,
                'action_name' => 'Short URL Visit',
                'url' => isset($visit['url_key']) ? 'https://short.link/'.$visit['url_key'] : null,
                'urlref' => $visit['referer_url'] ?? null,
                'cid' => isset($visit['ip_hash']) ? substr((string) $visit['ip_hash'], 0, 16) : null,
                'country' => $visit['country_code'] ?? null,
                'token_auth' => config('short-url.analytics.matomo.token_auth'),
            ]));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
