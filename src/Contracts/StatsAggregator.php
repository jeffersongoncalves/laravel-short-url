<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use DateTimeInterface;
use JeffersonGoncalves\LaravelShortUrl\Data\StatsPayload;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

interface StatsAggregator
{
    public function for(ShortUrl $shortUrl): static;

    public function between(DateTimeInterface $from, DateTimeInterface $to): static;

    public function get(): StatsPayload;
}
