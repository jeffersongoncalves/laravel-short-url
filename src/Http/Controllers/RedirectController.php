<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Pipeline\RedirectPipeline;
use Symfony\Component\HttpFoundation\Response;

class RedirectController
{
    public function __invoke(Request $request, string $urlKey, RedirectPipeline $pipeline): Response
    {
        abort_unless(in_array($request->getMethod(), ['GET', 'HEAD'], true), 404);

        return $pipeline->handle($request, $urlKey);
    }
}
