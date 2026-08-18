<?php

namespace JeffersonGoncalves\LaravelShortUrl\Support;

/**
 * Stateless, self-verifying "I already saw the warning" token for the
 * ShowWarning interstitial's continue link — no session write needed,
 * unlike password unlock.
 */
class WarningToken
{
    public static function generate(string $urlKey): string
    {
        $ttlMinutes = (int) config('short-url.security.warning.token_ttl_minutes', 30);
        $expires = now()->addMinutes($ttlMinutes)->timestamp;

        return $expires.'.'.self::signature($urlKey, $expires);
    }

    public static function isValid(string $urlKey, ?string $token): bool
    {
        if (! $token || ! str_contains($token, '.')) {
            return false;
        }

        [$expires, $signature] = explode('.', $token, 2);

        if (! ctype_digit($expires) || (int) $expires < now()->timestamp) {
            return false;
        }

        return hash_equals(self::signature($urlKey, (int) $expires), $signature);
    }

    protected static function signature(string $urlKey, int $expires): string
    {
        return hash_hmac('sha256', "{$urlKey}|{$expires}", (string) config('app.key'));
    }
}
