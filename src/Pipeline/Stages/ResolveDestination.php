<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use JeffersonGoncalves\LaravelShortUrl\Contracts\TargetingResolver;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;
use Throwable;

/**
 * "single" destinations skip this entirely (BuildFinalUrl reads
 * destination_url directly). "split"/"rules" run through the
 * TargetingResolver — any failure here falls back to the base
 * destination_url rather than breaking the redirect.
 */
class ResolveDestination
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $shortUrl = $context->shortUrl;

        if ($shortUrl && $shortUrl->destination_type !== 'single') {
            try {
                $destination = app(TargetingResolver::class)->resolve($shortUrl, $context->request);

                $context->destinationUrl = $destination->url;
                $context->tracking['selected_variant'] = $destination->variant;
                $context->tracking['matched_rule_index'] = $destination->matchedRuleIndex;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $next($context);
    }
}
