<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Jobs\TrackShortUrlVisitJob;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Support\AcceptLanguage;
use Throwable;

/**
 * Last stage: the response is already built. Dispatching (or, on the sync
 * queue connection, running) the tracking job is wrapped so nothing here
 * can ever turn a working redirect into a 500.
 */
class DispatchTracking
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if ($shortUrl && $shortUrl->track_visits) {
            try {
                TrackShortUrlVisitJob::dispatch($this->buildPayload($context));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $next($context);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(RedirectContext $context): array
    {
        $request = $context->request;
        $shortUrl = $context->shortUrl;
        $startedAt = $request->server('REQUEST_TIME_FLOAT');

        return [
            'short_url_id' => $shortUrl->id,
            'tenant_id' => $shortUrl->tenant_id,
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'referer_url' => $request->headers->get('referer'),
            'browser_language' => AcceptLanguage::preferred($request->headers->get('accept-language')),
            'app_host' => (string) $context->host,
            'is_bot' => (bool) ($context->tracking['is_bot'] ?? false),
            'device_type' => $context->tracking['device_type'] ?? null,
            'operating_system' => $context->tracking['operating_system'] ?? null,
            'is_qr_scan' => (bool) ($context->tracking['is_qr_scan'] ?? false),
            'is_vpn' => (bool) ($context->tracking['is_vpn'] ?? false),
            'is_proxy' => (bool) ($context->tracking['is_proxy'] ?? false),
            'is_tor' => (bool) ($context->tracking['is_tor'] ?? false),
            'is_datacenter' => (bool) ($context->tracking['is_datacenter'] ?? false),
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
            'selected_variant' => $context->tracking['selected_variant'] ?? null,
            'matched_rule_index' => $context->tracking['matched_rule_index'] ?? null,
            'response_time_ms' => is_numeric($startedAt) ? (int) ((microtime(true) - (float) $startedAt) * 1000) : null,
            'track_ip_address' => (bool) $shortUrl->track_ip_address,
            'track_browser' => (bool) $shortUrl->track_browser,
            'track_browser_version' => (bool) $shortUrl->track_browser_version,
            'track_operating_system' => (bool) $shortUrl->track_operating_system,
            'track_operating_system_version' => (bool) $shortUrl->track_operating_system_version,
            'track_device_type' => (bool) $shortUrl->track_device_type,
            'track_referer_url' => (bool) $shortUrl->track_referer_url,
            'track_browser_language' => (bool) $shortUrl->track_browser_language,
        ];
    }
}
