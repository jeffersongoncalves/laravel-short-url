<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class ImportReport
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public int $imported,
        public int $skipped,
        public int $failed,
        public array $errors = [],
    ) {}
}
