<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Models\Pixel;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Registries\PixelProviderRegistry;
use Throwable;

/**
 * Renders a brief client-side interstitial when there are pixel scripts to
 * fire before the browser navigation. Every other case (the vast majority
 * of redirects) skips this stage entirely and goes straight to Respond.
 */
class RenderInterstitial
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl) {
            return $next($context);
        }

        $pixelScripts = $this->resolvePixelScripts($shortUrl);

        if ($pixelScripts === []) {
            return $next($context);
        }

        return response()->view('short-url::interstitial', [
            'destinationUrl' => $context->finalUrl,
            'pixelScripts' => $pixelScripts,
            'requireConsent' => config('short-url.pixels.require_consent', false),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function resolvePixelScripts(ShortUrl $shortUrl): array
    {
        try {
            $registry = app(PixelProviderRegistry::class);

            return $shortUrl->pixels()
                ->get()
                ->map(function (Pixel $pixel) use ($registry) {
                    $provider = $registry->get($pixel->provider_key);

                    return $provider?->render($pixel->config);
                })
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
