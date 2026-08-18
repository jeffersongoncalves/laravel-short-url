<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Http\RedirectResponse;
use JeffersonGoncalves\LaravelShortUrl\Contracts\WebhookDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class CheckAvailability
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl || ! $shortUrl->is_enabled || $shortUrl->deactivated_at) {
            throw new NotFoundHttpException;
        }

        if ($shortUrl->expires_at && $shortUrl->expires_at->isPast()) {
            $this->notify('link.expired', $shortUrl);

            if ($shortUrl->expiration_redirect_url) {
                return new RedirectResponse($shortUrl->expiration_redirect_url);
            }

            return response()->view('short-url::expired', [], 410);
        }

        if ($shortUrl->max_visits !== null && $shortUrl->total_visits >= $shortUrl->max_visits) {
            $this->notify('link.limit_reached', $shortUrl);

            throw new NotFoundHttpException;
        }

        return $next($context);
    }

    protected function notify(string $event, ShortUrl $shortUrl): void
    {
        try {
            app(WebhookDispatcher::class)->dispatch($event, ['short_url_id' => $shortUrl->id, 'url_key' => $shortUrl->url_key], $shortUrl);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
