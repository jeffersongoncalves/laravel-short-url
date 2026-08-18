<?php

namespace JeffersonGoncalves\LaravelShortUrl\GeoIp;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;

/**
 * Trusts geo headers injected by a CDN/edge proxy in front of the app
 * (Cloudflare, CloudFront, ...). Zero latency, zero external calls — only
 * safe when SHORT_URL_TRUST_CDN_HEADERS is on and the edge is trusted.
 */
class HeadersGeoIpDriver implements GeoIpDriver
{
    public function __construct(protected Request $request) {}

    public function resolve(string $ip): GeoLocation
    {
        $headers = $this->request->headers;

        return new GeoLocation(
            country: $headers->get('CF-IPCountry-Name') ?? $headers->get('CloudFront-Viewer-Country-Name'),
            countryCode: $headers->get('CF-IPCountry') ?? $headers->get('CloudFront-Viewer-Country'),
            region: $headers->get('CloudFront-Viewer-Country-Region-Name'),
            city: $headers->get('CloudFront-Viewer-City'),
            latitude: $this->toFloat($headers->get('CloudFront-Viewer-Latitude')),
            longitude: $this->toFloat($headers->get('CloudFront-Viewer-Longitude')),
            timezone: $headers->get('CloudFront-Viewer-Time-Zone'),
        );
    }

    protected function toFloat(?string $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
