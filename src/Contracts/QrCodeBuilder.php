<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\QrDesign;

interface QrCodeBuilder
{
    public function design(QrDesign $design): static;

    public function toSvg(): string;

    public function toPng(): string;

    public function toPdf(): string;

    public function toEps(): string;
}
