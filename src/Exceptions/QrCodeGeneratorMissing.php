<?php

namespace JeffersonGoncalves\LaravelShortUrl\Exceptions;

use RuntimeException;

class QrCodeGeneratorMissing extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('QR code generation requires the optional endroid/qr-code package. Install it with: composer require endroid/qr-code');
    }
}
