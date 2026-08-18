<?php

namespace JeffersonGoncalves\LaravelShortUrl\Exceptions;

use RuntimeException;

class RequiredUtmParameterMissing extends RuntimeException
{
    public function __construct(public readonly string $parameter)
    {
        parent::__construct("Required UTM parameter missing: {$parameter}.");
    }
}
