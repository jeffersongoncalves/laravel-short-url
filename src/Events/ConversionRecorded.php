<?php

namespace JeffersonGoncalves\LaravelShortUrl\Events;

use Illuminate\Foundation\Events\Dispatchable;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;

class ConversionRecorded
{
    use Dispatchable;

    public function __construct(public Conversion $conversion) {}
}
