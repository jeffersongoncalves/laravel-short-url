<?php

namespace JeffersonGoncalves\LaravelShortUrl\GeoIp;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;

/**
 * Trusts geo headers injected by a CDN/edge proxy in front of the app
 * (Cloudflare, CloudFront, ...). Zero latency, zero external calls — only
 * active when short-url.tracking.trust_cdn_headers is on, since these
 * headers are trivially spoofable by a client that reaches the app
 * directly, bypassing the edge.
 *
 * resolve() reads the live request — safe when called on the request
 * thread. TrackShortUrlVisitJob normally runs in a queue worker, where the
 * originating request (and its headers) no longer exists, so it snapshots
 * the headers during the request via snapshot() and feeds them back
 * through resolveFromHeaders() instead.
 */
class HeadersGeoIpDriver implements GeoIpDriver
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'CF-IPCountry-Name',
        'CF-IPCountry',
        'CloudFront-Viewer-Country-Name',
        'CloudFront-Viewer-Country',
        'CloudFront-Viewer-Country-Region-Name',
        'CloudFront-Viewer-City',
        'CloudFront-Viewer-Latitude',
        'CloudFront-Viewer-Longitude',
        'CloudFront-Viewer-Time-Zone',
    ];

    public function __construct(protected Request $request) {}

    /**
     * @return array<string, string|null>
     */
    public static function snapshot(Request $request): array
    {
        $headers = [];

        foreach (self::HEADERS as $name) {
            $headers[$name] = $request->headers->get($name);
        }

        return $headers;
    }

    public function resolve(string $ip): GeoLocation
    {
        return $this->resolveFromHeaders(self::snapshot($this->request));
    }

    /**
     * @param  array<string, string|null>  $headers
     */
    public function resolveFromHeaders(array $headers): GeoLocation
    {
        if (! config('short-url.tracking.trust_cdn_headers', false)) {
            return new GeoLocation;
        }

        return new GeoLocation(
            country: $headers['CF-IPCountry-Name'] ?? $headers['CloudFront-Viewer-Country-Name'] ?? null,
            countryCode: $headers['CF-IPCountry'] ?? $headers['CloudFront-Viewer-Country'] ?? null,
            region: $headers['CloudFront-Viewer-Country-Region-Name'] ?? null,
            city: $headers['CloudFront-Viewer-City'] ?? null,
            latitude: $this->toFloat($headers['CloudFront-Viewer-Latitude'] ?? null),
            longitude: $this->toFloat($headers['CloudFront-Viewer-Longitude'] ?? null),
            timezone: $headers['CloudFront-Viewer-Time-Zone'] ?? null,
        );
    }

    protected function toFloat(?string $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
