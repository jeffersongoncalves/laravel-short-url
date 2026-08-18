<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Models\Pixel;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Registries\DeepLinkRegistry;
use JeffersonGoncalves\LaravelShortUrl\Registries\PixelProviderRegistry;
use Throwable;

/**
 * Renders a brief client-side interstitial when there's something to do
 * before the browser navigation: attempt a mobile app deep link, and/or
 * fire pixel scripts. Every other case (the vast majority of redirects)
 * skips this stage entirely and goes straight to Respond.
 */
class RenderInterstitial
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl) {
            return $next($context);
        }

        $schemeUrl = $this->resolveSchemeUrl($context);
        $pixelScripts = $this->resolvePixelScripts($shortUrl);

        if (! $schemeUrl && $pixelScripts === []) {
            return $next($context);
        }

        return response()->view('short-url::interstitial', [
            'destinationUrl' => $context->finalUrl,
            'schemeUrl' => $schemeUrl,
            'pixelScripts' => $pixelScripts,
            'requireConsent' => $pixelScripts !== [] && config('short-url.pixels.require_consent', false),
        ]);
    }

    protected function resolveSchemeUrl(RedirectContext $context): ?string
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl->auto_open_app_mobile) {
            return null;
        }

        if (($context->tracking['device_type'] ?? null) !== 'mobile') {
            return null;
        }

        if ($shortUrl->app_scheme_override) {
            return str_replace('{url}', urlencode($context->finalUrl), $shortUrl->app_scheme_override);
        }

        try {
            $app = app(DeepLinkRegistry::class)->forUrl($context->finalUrl);
        } catch (Throwable) {
            return null;
        }

        return $app?->buildSchemeUrl($context->finalUrl);
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
