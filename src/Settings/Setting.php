<?php

namespace JeffersonGoncalves\LaravelShortUrl\Settings;

use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;

/**
 * Effective value of a runtime setting: the stored SettingsRepository value
 * when a row exists, otherwise config('short-url.<key>') (which already
 * carries .env overrides), otherwise the package default.
 */
final class Setting
{
    public static function int(string $key, int $default): int
    {
        return (int) app(SettingsRepository::class)->get($key, config("short-url.{$key}", $default));
    }
}
