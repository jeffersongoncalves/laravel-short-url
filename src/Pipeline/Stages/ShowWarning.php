<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

// ponytail: stub — interstitial warning page arrives in a later phase.
class ShowWarning
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        return $next($context);
    }
}
