<?php

namespace JeffersonGoncalves\LaravelShortUrl\Support;

use Illuminate\Support\Facades\Session;

class PasswordUnlock
{
    public static function isUnlocked(int $shortUrlId): bool
    {
        $expiresAt = Session::get(self::sessionKey($shortUrlId));

        return is_int($expiresAt) && now()->timestamp < $expiresAt;
    }

    public static function unlock(int $shortUrlId): void
    {
        $ttlMinutes = (int) config('short-url.security.password.unlock_ttl_minutes', 60);

        Session::put(self::sessionKey($shortUrlId), now()->addMinutes($ttlMinutes)->timestamp);
    }

    public static function sessionKey(int $shortUrlId): string
    {
        return "short_url_unlocked:{$shortUrlId}";
    }
}
