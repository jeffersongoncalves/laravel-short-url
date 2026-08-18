<?php

namespace JeffersonGoncalves\LaravelShortUrl\Conversions;

use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

class NullConversionApiDispatcher implements ConversionApiDispatcher
{
    public function send(Conversion $conversion): void
    {
        // No S2S provider configured — recording the conversion locally is enough.
    }
}
