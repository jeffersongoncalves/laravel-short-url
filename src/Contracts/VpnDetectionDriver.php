<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\ThreatResult;

interface VpnDetectionDriver
{
    public function check(string $ip): ThreatResult;
}
