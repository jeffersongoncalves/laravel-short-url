<?php

namespace JeffersonGoncalves\LaravelShortUrl\GeoIp;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Contracts\GeoIpDriver;
use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;
use Throwable;

/**
 * Free-tier ip-api.com lookup. No integration failure may ever break a
 * redirect: any exception or non-success response yields an empty
 * GeoLocation instead of propagating.
 */
class IpApiGeoIpDriver implements GeoIpDriver
{
    public function resolve(string $ip): GeoLocation
    {
        try {
            $response = Http::timeout(1)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,countryCode,regionName,city,lat,lon,timezone,isp,as',
                ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
                return new GeoLocation;
            }

            return new GeoLocation(
                country: $response->json('country'),
                countryCode: $response->json('countryCode'),
                region: $response->json('regionName'),
                city: $response->json('city'),
                latitude: $response->json('lat'),
                longitude: $response->json('lon'),
                timezone: $response->json('timezone'),
                isp: $response->json('isp'),
                asn: $response->json('as'),
            );
        } catch (Throwable $e) {
            report($e);

            return new GeoLocation;
        }
    }
}
