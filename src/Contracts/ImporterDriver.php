<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\ImportPreview;
use JeffersonGoncalves\LaravelShortUrl\Data\ImportReport;

interface ImporterDriver
{
    public function preview(string $source): ImportPreview;

    public function import(string $source): ImportReport;
}
