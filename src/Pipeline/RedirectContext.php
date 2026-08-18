<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use Symfony\Component\HttpFoundation\Response;

class RedirectContext
{
    public ?string $host = null;

    public ?CustomDomain $customDomain = null;

    public ?ShortUrl $shortUrl = null;

    public ?string $destinationUrl = null;

    public string $finalUrl = '';

    public ?Response $response = null;

    /**
     * Free-form bag tracking-related stages fill in along the way (device
     * type, bot flag, ...), consumed by DispatchTracking at the end.
     *
     * @var array<string, mixed>
     */
    public array $tracking = [];

    public function __construct(
        public readonly Request $request,
        public readonly string $urlKey,
    ) {}
}
