<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class BuildFinalUrl
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $url = $context->destinationUrl ?? $context->shortUrl->destination_url;
        $queryString = $context->request->getQueryString();

        if ($context->shortUrl->forward_query_params && $queryString) {
            $url .= (str_contains($url, '?') ? '&' : '?').$queryString;
        }

        $context->finalUrl = $url;

        return $next($context);
    }
}
