<?php

namespace JeffersonGoncalves\LaravelShortUrl\Data;

readonly class QrDesign
{
    /**
     * @param  'square'|'dots'|'rounded'|'classy'|'extra-rounded'  $dotsStyle
     * @param  'L'|'M'|'Q'|'H'  $errorCorrection
     * @param  array{from: string, to: string, type: 'linear'|'radial'}|null  $gradient
     */
    public function __construct(
        public string $dotsStyle = 'square',
        public string $eyesStyle = 'square',
        public ?array $gradient = null,
        public int $margin = 0,
        public ?string $logoPath = null,
        public int $logoSizePercent = 20,
        public string $errorCorrection = 'M',
    ) {}
}
