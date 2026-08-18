<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

// ponytail: stub — split/rule-based destination resolution (TargetingResolver) arrives in F3.
// F1 only supports destination_type "single", already stored on destination_url.
class ResolveDestination
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        return $next($context);
    }
}
