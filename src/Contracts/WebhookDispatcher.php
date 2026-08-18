<?php

namespace JeffersonGoncalves\LaravelShortUrl\Contracts;

use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

interface WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, array $payload, ?ShortUrl $shortUrl = null): void;
}
