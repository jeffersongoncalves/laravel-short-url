<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class ImportPreview
{
    /**
     * @param  array<int, array<string, mixed>>  $sampleRows
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public int $totalRows,
        public array $sampleRows = [],
        public array $columns = [],
        public array $warnings = [],
    ) {}
}
