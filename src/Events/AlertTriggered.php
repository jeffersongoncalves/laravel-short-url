<?php

namespace JeffersonGoncalves\LaravelShortUrl\Events;

use Illuminate\Foundation\Events\Dispatchable;
use JeffersonGoncalves\LaravelShortUrl\Models\Alert;

class AlertTriggered
{
    use Dispatchable;

    public function __construct(public Alert $alert) {}
}
