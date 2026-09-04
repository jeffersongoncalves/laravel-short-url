<?php

namespace JeffersonGoncalves\LaravelShortUrl\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\QrCodeGeneratorMissing;

/**
 * Generates a QR code pointing at a short url's full link. Requires the
 * optional `endroid/qr-code` package (see composer.json "suggest") — never
 * a hard dependency, since most installs don't need it.
 */
class QrCodeGenerator
{
    public function __construct(
        protected string $data,
        protected int $size = 300,
        protected int $margin = 10,
    ) {}

    /**
     * @throws QrCodeGeneratorMissing
     */
    public function svg(): string
    {
        if (! class_exists(Builder::class) || ! class_exists(SvgWriter::class)) {
            throw new QrCodeGeneratorMissing;
        }

        return Builder::create()
            ->writer(new SvgWriter)
            ->data($this->data)
            ->size($this->size)
            ->margin($this->margin)
            ->build()
            ->getString();
    }

    /**
     * @throws QrCodeGeneratorMissing
     */
    public function png(): string
    {
        if (! class_exists(Builder::class) || ! class_exists(PngWriter::class)) {
            throw new QrCodeGeneratorMissing;
        }

        return Builder::create()
            ->writer(new PngWriter)
            ->data($this->data)
            ->size($this->size)
            ->margin($this->margin)
            ->build()
            ->getString();
    }

    /**
     * @throws QrCodeGeneratorMissing
     */
    public function dataUri(string $format = 'png'): string
    {
        if (! class_exists(Builder::class) || ! class_exists(SvgWriter::class) || ! class_exists(PngWriter::class)) {
            throw new QrCodeGeneratorMissing;
        }

        return Builder::create()
            ->writer($format === 'svg' ? new SvgWriter : new PngWriter)
            ->data($this->data)
            ->size($this->size)
            ->margin($this->margin)
            ->build()
            ->getDataUri();
    }
}
