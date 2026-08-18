<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class Destination
{
    public function __construct(
        public string $url,
        public ?string $variant = null,
        public ?int $matchedRuleIndex = null,
    ) {}
}
