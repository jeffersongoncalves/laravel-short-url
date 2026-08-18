<?php

use JeffersonGoncalves\LaravelShortUrl\Contracts\QrCodeBuilder;
use JeffersonGoncalves\LaravelShortUrl\Data\QrDesign;
use JeffersonGoncalves\LaravelShortUrl\Qr\EndroidQrCodeBuilder;

it('generates a valid svg document', function () {
    $svg = (new EndroidQrCodeBuilder('https://short.test/abc1234'))->toSvg();

    expect($svg)->toContain('<svg');
});

it('generates a valid png with the right magic bytes', function () {
    $png = (new EndroidQrCodeBuilder('https://short.test/abc1234'))->toPng();

    expect(substr($png, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('generates a valid pdf document', function () {
    $pdf = (new EndroidQrCodeBuilder('https://short.test/abc1234'))->toPdf();

    expect(substr($pdf, 0, 4))->toBe('%PDF');
});

it('generates a valid eps document', function () {
    $eps = (new EndroidQrCodeBuilder('https://short.test/abc1234'))->toEps();

    expect($eps)->toContain('%!PS-Adobe');
});

it('applies a custom design', function () {
    $svg = (new EndroidQrCodeBuilder('https://short.test/abc1234'))
        ->design(new QrDesign(margin: 5, errorCorrection: 'H'))
        ->toSvg();

    expect($svg)->toContain('<svg');
});

it('resolves through the container with data injected', function () {
    $builder = app()->makeWith(QrCodeBuilder::class, ['data' => 'https://short.test/xyz9876']);

    expect($builder->toSvg())->toContain('<svg');
});
