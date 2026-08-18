<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\VerificationResult;

interface DnsVerifier
{
    public function verify(string $domain, string $expectedToken): VerificationResult;
}
