<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline\Stages;

use Closure;
use Illuminate\Http\RedirectResponse;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectContext;

class Respond
{
    public function __invoke(RedirectContext $context, Closure $next): mixed
    {
        $context->response = new RedirectResponse(
            $context->finalUrl,
            $context->shortUrl->redirect_status_code
        );

        return $next($context);
    }
}
