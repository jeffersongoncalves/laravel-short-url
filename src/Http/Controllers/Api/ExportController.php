<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\Response;
use JeffersonGoncalves\LaravelShortUrl\Exporters\CsvLinkExporter;

class ExportController
{
    public function csv(CsvLinkExporter $exporter): Response
    {
        return response($exporter->toCsvString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="short-urls.csv"',
        ]);
    }
}
