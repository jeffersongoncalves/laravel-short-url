<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class FilterType
{
    /**
     * @param  array<int, array{value: string, label: string}>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $fieldType,
        public ?string $icon = null,
        public array $options = [],
    ) {}
}
