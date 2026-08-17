<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use Symfony\Component\HttpFoundation\Response;

class RedirectContext
{
    public ?string $host = null;

    public ?ShortUrl $shortUrl = null;

    public string $finalUrl = '';

    public ?Response $response = null;

    public function __construct(
        public readonly Request $request,
        public readonly string $urlKey,
    ) {}
}
