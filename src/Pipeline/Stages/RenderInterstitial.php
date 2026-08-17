<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

// ponytail: stub — interstitial rendering (e.g. "open in app") arrives in a later phase.
class RenderInterstitial
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        return $next($context);
    }
}
