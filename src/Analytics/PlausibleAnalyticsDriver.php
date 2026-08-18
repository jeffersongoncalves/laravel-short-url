<?php

namespace JeffersonGoncalves\LaravelShortUrl\Analytics;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\AnalyticsDriver;
use Throwable;

class PlausibleAnalyticsDriver implements AnalyticsDriver
{
    public function record(array $visit): void
    {
        $domain = config('short-url.analytics.plausible.domain');

        if (! $domain) {
            return;
        }

        try {
            $apiHost = rtrim((string) config('short-url.analytics.plausible.api_host', 'https://plausible.io'), '/');

            Http::timeout(2)->post("{$apiHost}/api/event", [
                'name' => 'pageview',
                'domain' => $domain,
                'url' => 'https://'.$domain.'/'.($visit['url_key'] ?? ''),
                'referrer' => $visit['referer_url'] ?? null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
