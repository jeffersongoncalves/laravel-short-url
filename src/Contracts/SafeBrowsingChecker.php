<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Data\SafetyResult;

interface SafeBrowsingChecker
{
    public function check(string $url): SafetyResult;
}
