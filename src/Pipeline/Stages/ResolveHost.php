<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class ResolveHost
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $context->host = config('short-url.route.domain') ?? $context->request->getHost();

        return $next($context);
    }
}
