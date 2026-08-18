<?php

namespace JeffersonGoncalves\LaravelShortUrl\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl as ShortUrlModel;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlBuilder;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;

/**
 * @method static ShortUrlModel create(array $attributes)
 * @method static ShortUrlBuilder destination(string $url)
 * @method static ShortUrlModel|null resolve(string $key, ?string $host = null)
 *
 * @see ShortUrlManager
 */
class ShortUrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShortUrlManager::class;
    }
}
