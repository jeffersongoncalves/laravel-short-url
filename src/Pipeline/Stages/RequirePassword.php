<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

// ponytail: stub — password-protected links arrive in a later phase.
class RequirePassword
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        return $next($context);
    }
}
