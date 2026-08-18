<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use JeffersonGoncalves\LaravelShortUrl\Support\WarningToken;

class ShowWarning
{
    protected const TOKEN_PARAM = '_swt';

    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if (! $shortUrl || ! $shortUrl->show_warning_page) {
            return $next($context);
        }

        $token = $context->request->query(self::TOKEN_PARAM);

        if (WarningToken::isValid($context->urlKey, is_string($token) ? $token : null)) {
            return $next($context);
        }

        return response()->view('short-url::warning', [
            'destinationUrl' => $shortUrl->destination_url,
            'message' => $shortUrl->warning_message,
            'continueUrl' => $this->continueUrl($context),
        ]);
    }

    protected function continueUrl(RedirectContext $context): string
    {
        $query = $context->request->query();
        $query[self::TOKEN_PARAM] = WarningToken::generate($context->urlKey);

        return $context->request->url().'?'.http_build_query($query);
    }
}
