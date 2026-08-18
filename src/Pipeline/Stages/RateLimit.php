<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class RateLimit
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        if (! config('short-url.security.rate_limit.enabled', false)) {
            return $next($context);
        }

        $key = 'short-url:'.$context->request->ip();
        $maxAttempts = (int) config('short-url.security.rate_limit.max_attempts', 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return new Response('', 429, ['Retry-After' => RateLimiter::availableIn($key)]);
        }

        RateLimiter::hit($key, (int) config('short-url.security.rate_limit.decay_seconds', 60));

        return $next($context);
    }
}
