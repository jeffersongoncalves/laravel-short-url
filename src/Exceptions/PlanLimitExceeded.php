<?php

namespace JeffersonGoncalves\LaravelShortUrl\Exceptions;

use RuntimeException;

class PlanLimitExceeded extends RuntimeException
{
    public function __construct(public readonly string $limitKey, public readonly int $limit)
    {
        parent::__construct("Plan limit exceeded: {$limitKey} (limit: {$limit}).");
    }
}
