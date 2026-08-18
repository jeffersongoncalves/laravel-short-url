<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

use DateTimeInterface;

readonly class SafetyResult
{
    /**
     * @param  'safe'|'unsafe'|'unknown'  $status
     * @param  array<int, string>  $threats
     */
    public function __construct(
        public string $status,
        public ?DateTimeInterface $checkedAt = null,
        public array $threats = [],
    ) {}
}
