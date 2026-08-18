<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Contracts\VpnDetectionDriver;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DetectVpnProxy
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $mode = config('short-url.security.vpn_detection.mode', 'off');
        $ip = (string) $context->request->ip();

        if ($mode === 'off' || $ip === '') {
            return $next($context);
        }

        $result = app(VpnDetectionDriver::class)->check($ip);

        $context->tracking['is_vpn'] = $result->isVpn;
        $context->tracking['is_proxy'] = $result->isProxy;
        $context->tracking['is_tor'] = $result->isTor;
        $context->tracking['is_datacenter'] = $result->isDatacenter;

        if ($mode === 'block' && ($result->isVpn || $result->isProxy || $result->isTor)) {
            throw new HttpException(403, trans('short-url::interstitials.vpn_blocked'));
        }

        return $next($context);
    }
}
