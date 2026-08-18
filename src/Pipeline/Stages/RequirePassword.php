<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Support\PasswordUnlock;

class RequirePassword
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl || ! $shortUrl->password_hash) {
            return $next($context);
        }

        if (PasswordUnlock::isUnlocked($shortUrl->id)) {
            return $next($context);
        }

        return response()->view('short-url::password', [
            'urlKey' => $context->urlKey,
            'hint' => $shortUrl->password_hint,
        ], 401);
    }
}
