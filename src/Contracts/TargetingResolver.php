<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Data\Destination;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

interface TargetingResolver
{
    public function resolve(ShortUrl $shortUrl, Request $request): Destination;
}
