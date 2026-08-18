<?php

namespace JeffersonGoncalves\LaravelShortUrl\Events;

use Illuminate\Foundation\Events\Dispatchable;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Visit;

class ShortUrlVisited
{
    use Dispatchable;

    public function __construct(
        public ShortUrl $shortUrl,
        public Visit $visit,
    ) {}
}
