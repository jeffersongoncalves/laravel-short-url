<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers;

use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelShortUrl\Contracts\QrCodeBuilder;
use JeffersonGoncalves\LaravelShortUrl\Data\QrDesign;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QrCodeController
{
    protected const FORMATS = [
        'svg' => ['method' => 'toSvg', 'mime' => 'image/svg+xml'],
        'png' => ['method' => 'toPng', 'mime' => 'image/png'],
        'pdf' => ['method' => 'toPdf', 'mime' => 'application/pdf'],
        'eps' => ['method' => 'toEps', 'mime' => 'application/postscript'],
    ];

    public function __invoke(Request $request, string $urlKey): Response
    {
        $shortUrl = ShortUrl::findByKey($urlKey);

        if (! $shortUrl) {
            throw new NotFoundHttpException;
        }

        $format = strtolower((string) $request->query('format', 'svg'));

        if (! isset(self::FORMATS[$format])) {
            throw new NotFoundHttpException;
        }

        $target = url($urlKey).'?source=qr';
        $design = $shortUrl->qr_design ? new QrDesign(...$shortUrl->qr_design) : new QrDesign;

        $builder = app()->makeWith(QrCodeBuilder::class, ['data' => $target])->design($design);
        $method = self::FORMATS[$format]['method'];

        return response($builder->{$method}(), 200, ['Content-Type' => self::FORMATS[$format]['mime']]);
    }
}
