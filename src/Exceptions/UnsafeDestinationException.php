<?php

namespace JeffersonGoncalves\LaravelShortUrl\Exceptions;

use RuntimeException;

class UnsafeDestinationException extends RuntimeException
{
    /**
     * @param  array<int, string>  $threats
     */
    public function __construct(public readonly string $url, public readonly array $threats = [])
    {
        parent::__construct("Destination flagged unsafe by Safe Browsing: {$url} (".implode(', ', $threats).')');
    }
}
