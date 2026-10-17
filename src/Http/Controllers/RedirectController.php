<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectPipeline;
use Symfony\Component\HttpFoundation\Response;

class RedirectController
{
    // $urlKey goes last: an empty fallback placeholder is dropped from the route parameters,
    // so it must default instead of letting the pipeline slide into its position.
    public function __invoke(Request $request, RedirectPipeline $pipeline, ?string $urlKey = null): Response
    {
        abort_if(blank($urlKey) || ! in_array($request->getMethod(), ['GET', 'HEAD'], true), 404);

        return $pipeline->handle($request, $urlKey);
    }
}
