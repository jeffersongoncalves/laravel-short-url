<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

/**
 * Server-to-server conversion forwarding (Meta CAPI, Google Enhanced
 * Conversions, TikTok Events API, LinkedIn CAPI, ...). Ship one concrete
 * driver (Meta) as the reference implementation; add more the same way
 * GeoIpDriver/VpnDetectionDriver do.
 */
interface ConversionApiDispatcher
{
    public function send(Conversion $conversion): void;
}
