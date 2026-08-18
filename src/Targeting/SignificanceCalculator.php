<?php

namespace JeffersonGoncalves\LaravelShortUrl\Targeting;

/**
 * Two-proportion Z-test, used to tell whether an A/B rotation variant's
 * share of traffic differs from the control by more than chance.
 */
class SignificanceCalculator
{
    public static function zScore(int $successA, int $totalA, int $successB, int $totalB): ?float
    {
        if ($totalA === 0 || $totalB === 0) {
            return null;
        }

        $p1 = $successA / $totalA;
        $p2 = $successB / $totalB;
        $pooled = ($successA + $successB) / ($totalA + $totalB);

        $standardError = sqrt($pooled * (1 - $pooled) * (1 / $totalA + 1 / $totalB));

        if ($standardError <= 0.0) {
            return null;
        }

        return ($p1 - $p2) / $standardError;
    }

    public static function isSignificant(?float $zScore, float $threshold = 1.96): bool
    {
        return $zScore !== null && abs($zScore) >= $threshold;
    }
}
