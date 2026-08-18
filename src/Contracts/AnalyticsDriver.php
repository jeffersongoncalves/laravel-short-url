<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

interface AnalyticsDriver
{
    // ponytail: takes a plain visit array until the Visit model lands in F2.
    /**
     * @param  array<string, mixed>  $visit
     */
    public function record(array $visit): void;
}
