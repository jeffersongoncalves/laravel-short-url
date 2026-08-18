<?php

namespace JeffersonGoncalves\LaravelShortUrl\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\EpsWriter;
use Endroid\QrCode\Writer\PdfWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use JeffersonGoncalves\LaravelShortUrl\Contracts\QrCodeBuilder;
use JeffersonGoncalves\LaravelShortUrl\Data\QrDesign;
use RuntimeException;

/**
 * Wraps endroid/qr-code (suggest-only dependency — install it to use QR
 * generation at all; every method here throws a clear RuntimeException
 * when it's missing rather than a confusing class-not-found).
 *
 * endroid/qr-code v6 has no native per-eye styling or multi-stop gradient
 * support, so QrDesign's eyesStyle/gradient are accepted but not rendered
 * — dotsStyle maps to its nearest RoundBlockSizeMode and the foreground
 * color falls back to the gradient's "from" color when a gradient is set.
 */
class EndroidQrCodeBuilder implements QrCodeBuilder
{
    protected string $data = '';

    protected QrDesign $design;

    public function __construct(string $data = '')
    {
        $this->data = $data;
        $this->design = new QrDesign;
    }

    public function design(QrDesign $design): static
    {
        $this->design = $design;

        return $this;
    }

    public function toSvg(): string
    {
        return $this->build(new SvgWriter)->getString();
    }

    public function toPng(): string
    {
        return $this->build(new PngWriter)->getString();
    }

    public function toPdf(): string
    {
        return $this->build(new PdfWriter)->getString();
    }

    public function toEps(): string
    {
        return $this->build(new EpsWriter)->getString();
    }

    protected function build(mixed $writer): mixed
    {
        if (! class_exists(Builder::class)) {
            throw new RuntimeException('endroid/qr-code is required to generate QR codes. Run: composer require endroid/qr-code');
        }

        $foreground = $this->design->gradient['from'] ?? '#000000';

        $builder = new Builder(
            writer: $writer,
            data: $this->data,
            errorCorrectionLevel: $this->errorCorrectionLevel(),
            margin: $this->design->margin,
            roundBlockSizeMode: $this->roundBlockSizeMode(),
            foregroundColor: $this->hexToColor($foreground),
            backgroundColor: new Color(255, 255, 255),
        );

        $logoPath = $this->design->logoPath && is_file($this->design->logoPath)
            ? $this->design->logoPath
            : null;

        return $builder->build(logoPath: $logoPath);
    }

    protected function errorCorrectionLevel(): ErrorCorrectionLevel
    {
        return match ($this->design->errorCorrection) {
            'L' => ErrorCorrectionLevel::Low,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
            default => ErrorCorrectionLevel::Medium,
        };
    }

    protected function roundBlockSizeMode(): RoundBlockSizeMode
    {
        return match ($this->design->dotsStyle) {
            'dots', 'rounded', 'extra-rounded' => RoundBlockSizeMode::Margin,
            default => RoundBlockSizeMode::None,
        };
    }

    protected function hexToColor(string $hex): Color
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return new Color(0, 0, 0);
        }

        return new Color(
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        );
    }
}
