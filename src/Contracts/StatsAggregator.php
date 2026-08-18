<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use DateTimeInterface;
use JeffersonGoncalves\LaravelShortUrl\Data\StatsPayload;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

interface StatsAggregator
{
    public function for(ShortUrl $shortUrl): static;

    /**
     * Aggregates across every id in $shortUrlIds instead of a single link —
     * for a dashboard breakdown across a set of links (a folder, a tag, or
     * every link a tenant owns). Resolving which links belong in the set is
     * the caller's job (via ShortUrl's own tenant-scoped Eloquent query);
     * this only does the aggregation math.
     *
     * @param  array<int, int>  $shortUrlIds
     */
    public function forShortUrls(array $shortUrlIds): static;

    public function between(DateTimeInterface $from, DateTimeInterface $to): static;

    public function get(): StatsPayload;
}
