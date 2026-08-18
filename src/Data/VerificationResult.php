<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

use DateTimeInterface;

readonly class VerificationResult
{
    public function __construct(
        public bool $verified,
        public ?string $method = null,
        public ?DateTimeInterface $checkedAt = null,
        public ?string $error = null,
    ) {}
}
