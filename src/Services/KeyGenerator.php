<?php

namespace JeffersonGoncalves\LaravelShortUrl\Services;

use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use RuntimeException;

class KeyGenerator
{
    public const MAX_ATTEMPTS = 10;

    /**
     * Generate a unique, non-blacklisted Base62 short URL key.
     */
    public function generate(?int $customDomainId = null, ?int $length = null): string
    {
        $length ??= (int) config('short-url.key.length', 7);
        $blacklist = array_map('strtolower', (array) config('short-url.key.blacklist', []));

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $key = Str::random($length);

            if (! in_array(strtolower($key), $blacklist, true) && ! $this->keyExists($key, $customDomainId)) {
                return $key;
            }
        }

        throw new RuntimeException('Unable to generate a unique short URL key after '.self::MAX_ATTEMPTS.' attempts.');
    }

    protected function keyExists(string $key, ?int $customDomainId): bool
    {
        return ShortUrl::query()
            ->where('url_key', $key)
            ->where('custom_domain_id', $customDomainId)
            ->exists();
    }
}
