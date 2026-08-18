<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Support\Arr;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class BuildFinalUrl
{
    /**
     * @var array<int, string>
     */
    protected const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;
        $url = $context->destinationUrl ?? $shortUrl->destination_url;
        $queryString = $context->request->getQueryString();
        $query = [];

        if ($shortUrl->forward_query_params && $queryString) {
            parse_str($queryString, $query);

            if ($shortUrl->strip_utm_from_destination) {
                $query = Arr::except($query, self::UTM_KEYS);
            }
        }

        // The link's own utm_* values are authoritative for attribution on
        // the destination page — they override whatever the incoming click
        // carried, so the landing page's own analytics see the channel the
        // link was actually tagged with at creation.
        $query = array_merge($query, array_filter(Arr::only($shortUrl->getAttributes(), self::UTM_KEYS)));

        $context->finalUrl = $query ? $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query) : $url;

        return $next($context);
    }
}
