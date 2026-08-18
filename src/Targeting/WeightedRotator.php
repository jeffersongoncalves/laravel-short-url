<?php

namespace JeffersonGoncalves\LaravelShortUrl\Targeting;

class WeightedRotator
{
    /**
     * @param  array<int, array{url: string, weight: int, label?: string}>  $variants
     * @return array{url: string, weight: int, label?: string}|null
     */
    public static function pick(array $variants, ?string $stickyKey = null): ?array
    {
        $variants = array_values($variants);

        if ($variants === []) {
            return null;
        }

        $total = (int) array_sum(array_column($variants, 'weight'));

        if ($total <= 0) {
            return $variants[0];
        }

        $roll = $stickyKey !== null
            ? self::deterministicRoll($stickyKey, $total)
            : random_int(0, $total - 1);

        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += (int) $variant['weight'];

            if ($roll < $cumulative) {
                return $variant;
            }
        }

        return $variants[array_key_last($variants)];
    }

    /**
     * Deterministic pick for the same key, so a returning visitor keeps
     * seeing the same variant without needing a cookie/session.
     */
    protected static function deterministicRoll(string $key, int $total): int
    {
        return crc32($key) % $total;
    }
}
