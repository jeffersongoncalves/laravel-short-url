<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Support\BotDetector;
use JeffersonGoncalves\LaravelShortUrl\Support\UserAgentParser;

/**
 * Fast-path only: UA signature match plus device type/OS name, cheap enough
 * to run inline on every redirect. Full UA parsing happens later, off the
 * request path, inside TrackShortUrlVisitJob.
 */
class DetectBot
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $userAgent = (string) $context->request->userAgent();

        $context->tracking['is_bot'] = BotDetector::isBot($userAgent);
        $context->tracking['device_type'] = UserAgentParser::fastDeviceType($userAgent);
        $context->tracking['operating_system'] = UserAgentParser::fastOperatingSystem($userAgent);
        $context->tracking['is_qr_scan'] = $context->request->query('source') === 'qr';

        return $next($context);
    }
}
