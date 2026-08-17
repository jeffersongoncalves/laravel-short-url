<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

// ponytail: stub — bot detection arrives in a later phase.
class DetectBot
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        return $next($context);
    }
}
