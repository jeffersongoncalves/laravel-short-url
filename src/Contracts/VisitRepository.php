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
     * Deletes visit rows older than $before. When $tenantId is given, only
     * that tenant's rows are pruned — used to apply a per-tenant plan
     * retention window instead of the package-wide default.
     */
    public function prune(DateTimeInterface $before, int|string|null $tenantId = null): int;
}
