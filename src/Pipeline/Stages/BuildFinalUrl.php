<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

/**
 * F1 only supports the "single" destination type. Split testing, rule-based
 * and geo-fenced destinations are deferred to a later phase.
 */
class BuildFinalUrl
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $url = $context->shortUrl->destination_url;
        $queryString = $context->request->getQueryString();

        if ($context->shortUrl->forward_query_params && $queryString) {
            $url .= (str_contains($url, '?') ? '&' : '?').$queryString;
        }

        $context->finalUrl = $url;

        return $next($context);
    }
}
