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

    public function prune(DateTimeInterface $before): int;
}
