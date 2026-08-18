<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VisitRepository;
use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;
use JeffersonGoncalves\LaravelShortUrl\Events\ShortUrlVisited;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;
use JeffersonGoncalves\LaravelShortUrl\Services\CounterBuffer;
use JeffersonGoncalves\LaravelShortUrl\Support\IpAnonymizer;
use JeffersonGoncalves\LaravelShortUrl\Support\RefererClassifier;
use JeffersonGoncalves\LaravelShortUrl\Support\UserAgentParser;
use Throwable;

/**
 * Turns the raw payload captured on the request thread into a stored visit
 * row. Whether this runs inline (queue connection "sync") or on a worker is
 * entirely up to the app's queue config — either way, any failure here must
 * never surface past this job, so the whole thing is one error boundary.
 *
 * @phpstan-type Payload array{
 *     short_url_id: int, tenant_id: int|null, ip: string, user_agent: string,
 *     referer_url: string|null, browser_language: string|null, app_host: string,
 *     is_bot: bool, device_type: string|null, operating_system: string|null,
 *     is_qr_scan: bool, is_vpn: bool, is_proxy: bool, is_tor: bool, is_datacenter: bool,
 *     utm_source: string|null, utm_medium: string|null,
 *     utm_campaign: string|null, utm_term: string|null, utm_content: string|null,
 *     selected_variant: string|null, matched_rule_index: int|null,
 *     response_time_ms: int|null, track_ip_address: bool, track_browser: bool,
 *     track_browser_version: bool, track_operating_system: bool,
 *     track_operating_system_version: bool, track_device_type: bool,
 *     track_referer_url: bool, track_browser_language: bool,
 * }
 */
class TrackShortUrlVisitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Payload  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(VisitRepository $visits, CounterBuffer $counters, GeoIpDriver $geoIp): void
    {
        try {
            $this->track($visits, $counters, $geoIp);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @phpstan-param VisitRepository $visits
     */
    protected function track(VisitRepository $visits, CounterBuffer $counters, GeoIpDriver $geoIp): void
    {
        $payload = $this->payload;
        $ip = $payload['ip'];
        $isBot = $payload['is_bot'];

        $ipAttributes = $this->ipAttributes($payload);
        $geo = $payload['track_ip_address'] && $ip !== '' ? $this->resolveGeo($geoIp, $ip) : new GeoLocation;
        $ua = $this->userAgentAttributes($payload);
        $referer = $this->refererAttributes($payload);

        // No ip_hash to dedupe against (untracked/analytics-only) — can't
        // tell repeat visitors apart, so every visit counts as unique.
        $isUnique = ! $isBot
            && (! $ipAttributes['ip_hash'] || ! Visit::query()
                ->where('short_url_id', $payload['short_url_id'])
                ->where('ip_hash', $ipAttributes['ip_hash'])
                ->exists());

        $attributes = array_merge($ipAttributes, $ua, $referer, [
            'short_url_id' => $payload['short_url_id'],
            'tenant_id' => $payload['tenant_id'],
            'visited_at' => now(),
            'country' => $geo->country,
            'country_code' => $geo->countryCode,
            'region' => $geo->region,
            'city' => $geo->city,
            'latitude' => $geo->latitude,
            'longitude' => $geo->longitude,
            'timezone' => $geo->timezone,
            'isp' => $geo->isp,
            'asn' => $geo->asn,
            'browser_language' => $payload['track_browser_language'] ? $payload['browser_language'] : null,
            'user_agent_hash' => config('short-url.compliance.analytics_only', false) ? null : hash('sha256', $payload['user_agent']),
            'utm_source' => $payload['utm_source'],
            'utm_medium' => $payload['utm_medium'],
            'utm_campaign' => $payload['utm_campaign'],
            'utm_term' => $payload['utm_term'],
            'utm_content' => $payload['utm_content'],
            'is_qr_scan' => $payload['is_qr_scan'],
            'is_bot' => $isBot,
            'is_vpn' => $payload['is_vpn'],
            'is_proxy' => $payload['is_proxy'],
            'is_tor' => $payload['is_tor'],
            'is_datacenter' => $payload['is_datacenter'],
            'selected_variant' => $payload['selected_variant'],
            'matched_rule_index' => $payload['matched_rule_index'],
            'response_time_ms' => $payload['response_time_ms'],
            'created_at' => now(),
        ]);

        $visits->store($attributes);

        $counters->increment($payload['short_url_id'], [
            'total_visits' => $isBot ? 0 : 1,
            'unique_visits' => $isUnique ? 1 : 0,
            'qr_scans' => $payload['is_qr_scan'] ? 1 : 0,
            'bot_visits' => $isBot ? 1 : 0,
        ]);

        if (! $isBot) {
            ShortUrl::query()->where('id', $payload['short_url_id'])->update(['last_visited_at' => now()]);
        }

        $shortUrl = ShortUrl::query()->find($payload['short_url_id']);

        if ($shortUrl) {
            ShortUrlVisited::dispatch($shortUrl, new Visit($attributes));
        }
    }

    /**
     * @param  Payload  $payload
     * @return array{ip_hash: ?string, ip_anonymized: ?string, ip_version: ?int}
     */
    protected function ipAttributes(array $payload): array
    {
        $analyticsOnly = (bool) config('short-url.compliance.analytics_only', false);

        if (! $payload['track_ip_address'] || $payload['ip'] === '' || $analyticsOnly) {
            return ['ip_hash' => null, 'ip_anonymized' => null, 'ip_version' => null];
        }

        return [
            'ip_hash' => IpAnonymizer::hash($payload['ip']),
            'ip_anonymized' => IpAnonymizer::truncate($payload['ip']),
            'ip_version' => IpAnonymizer::version($payload['ip']),
        ];
    }

    /**
     * @param  Payload  $payload
     * @return array{device_type: ?string, browser: ?string, browser_version: ?string, operating_system: ?string, operating_system_version: ?string}
     */
    protected function userAgentAttributes(array $payload): array
    {
        $parsed = UserAgentParser::parse($payload['user_agent']);

        return [
            'device_type' => $payload['track_device_type'] ? $payload['device_type'] : null,
            'browser' => $payload['track_browser'] ? $parsed['browser'] : null,
            'browser_version' => $payload['track_browser_version'] ? $parsed['browser_version'] : null,
            'operating_system' => $payload['track_operating_system'] ? $payload['operating_system'] : null,
            'operating_system_version' => $payload['track_operating_system_version'] ? $parsed['operating_system_version'] : null,
        ];
    }

    /**
     * @param  Payload  $payload
     * @return array{referer_url: ?string, referer_host: ?string, referer_type: ?string}
     */
    protected function refererAttributes(array $payload): array
    {
        if (! $payload['track_referer_url']) {
            return ['referer_url' => null, 'referer_host' => null, 'referer_type' => null];
        }

        return [
            'referer_url' => $payload['referer_url'],
            'referer_host' => $payload['referer_url'] ? parse_url($payload['referer_url'], PHP_URL_HOST) : null,
            'referer_type' => RefererClassifier::classify($payload['referer_url'], $payload['app_host'], $payload['is_qr_scan']),
        ];
    }

    protected function resolveGeo(GeoIpDriver $geoIp, string $ip): GeoLocation
    {
        try {
            return $geoIp->resolve($ip);
        } catch (Throwable $e) {
            report($e);

            return new GeoLocation;
        }
    }
}
