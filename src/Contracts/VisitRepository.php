<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use DateTimeInterface;

interface VisitRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): void;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function query(int $shortUrlId, array $filters = []): array;

    /**
     * @return array<string, mixed>
     */
    public function aggregate(int $shortUrlId, DateTimeInterface $from, DateTimeInterface $to): array;

    /**
     * Same as aggregate(), summed across every id in $shortUrlIds instead of
     * one link — for a dashboard breakdown across a set of links (a folder,
     * a tag, or every link a tenant owns). The caller resolves which links
     * belong in the set via ShortUrl's own (already tenant-scoped) Eloquent
     * query; this only does the aggregation math, never link selection.
     *
     * @param  array<int, int>  $shortUrlIds
     * @return array<string, mixed>
     */
    public function aggregateMany(array $shortUrlIds, DateTimeInterface $from, DateTimeInterface $to): array;

    /**
     * Deletes visit rows older than $before. When $tenantId is given, only
     * that tenant's rows are pruned — used to apply a per-tenant plan
     * retention window instead of the package-wide default.
     */
    public function prune(DateTimeInterface $before, int|string|null $tenantId = null): int;
}
