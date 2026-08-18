<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\GeoLocation;

interface GeoIpDriver
{
    public function resolve(string $ip): GeoLocation;
}
