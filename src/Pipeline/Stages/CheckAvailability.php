<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Http\RedirectResponse;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckAvailability
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl || ! $shortUrl->is_enabled || $shortUrl->deactivated_at) {
            throw new NotFoundHttpException;
        }

        if ($shortUrl->expires_at && $shortUrl->expires_at->isPast()) {
            if ($shortUrl->expiration_redirect_url) {
                return new RedirectResponse($shortUrl->expiration_redirect_url);
            }

            return response()->view('short-url::expired', [], 410);
        }

        if ($shortUrl->max_visits !== null && $shortUrl->total_visits >= $shortUrl->max_visits) {
            throw new NotFoundHttpException;
        }

        return $next($context);
    }
}
