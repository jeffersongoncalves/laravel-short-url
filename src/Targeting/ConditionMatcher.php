<?php

namespace JeffersonGoncalves\LaravelShortUrl\Targeting;

use Illuminate\Support\Carbon;

/**
 * Evaluates a single targeting-rule condition against the current
 * visitor's resolved context. Unknown/malformed conditions never match —
 * a broken rule should fall through to the next one, not break the
 * redirect.
 *
 * Context shape:
 * device_type, operating_system, browser, country_code, region, city,
 * browser_language, referer_host, referer_type, utm (array), query (array),
 * visit_count (int), is_vpn (bool), is_bot (bool).
 */
class ConditionMatcher
{
    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $context
     */
    public static function matches(array $condition, array $context): bool
    {
        $type = $condition['type'] ?? null;

        return match ($type) {
            'device' => self::matchesValue($context['device_type'] ?? null, $condition),
            'platform' => self::matchesValue($context['operating_system'] ?? null, $condition),
            'browser' => self::matchesValue($context['browser'] ?? null, $condition),
            'country' => self::matchesValue($context['country_code'] ?? null, $condition),
            'region' => self::matchesValue($context['region'] ?? null, $condition),
            'city' => self::matchesValue($context['city'] ?? null, $condition),
            'language' => self::matchesLanguage($context['browser_language'] ?? null, $condition),
            'referer' => self::matchesReferer($context, $condition),
            'utm' => self::matchesUtm($context, $condition),
            'datetime' => self::matchesDatetime($condition),
            'visit_count' => self::matchesVisitCount((int) ($context['visit_count'] ?? 0), $condition),
            'is_vpn' => (bool) ($context['is_vpn'] ?? false) === (bool) ($condition['value'] ?? true),
            'is_bot' => (bool) ($context['is_bot'] ?? false) === (bool) ($condition['value'] ?? true),
            'query_param' => self::matchesQueryParam($context, $condition),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesValue(?string $actual, array $condition): bool
    {
        if ($actual === null) {
            return false;
        }

        $operator = $condition['operator'] ?? 'in';
        $expected = $condition['value'] ?? null;
        $values = is_array($expected) ? $expected : [$expected];
        $values = array_map(fn ($v) => strtolower((string) $v), $values);
        $actual = strtolower($actual);

        return match ($operator) {
            'not_in', 'not_equals' => ! in_array($actual, $values, true),
            default => in_array($actual, $values, true),
        };
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesLanguage(?string $actual, array $condition): bool
    {
        if (! $actual || ! isset($condition['value'])) {
            return false;
        }

        $expected = (string) $condition['value'];

        if (strcasecmp($actual, $expected) === 0) {
            return true;
        }

        $actualPrimary = strtolower(explode('-', $actual)[0]);
        $expectedPrimary = strtolower(explode('-', $expected)[0]);

        return $actualPrimary === $expectedPrimary;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesReferer(array $context, array $condition): bool
    {
        $operator = $condition['operator'] ?? 'contains';
        $expected = strtolower((string) ($condition['value'] ?? ''));

        if ($operator === 'type') {
            return strtolower((string) ($context['referer_type'] ?? '')) === $expected;
        }

        $host = strtolower((string) ($context['referer_host'] ?? ''));

        return $operator === 'equals' ? $host === $expected : ($expected !== '' && str_contains($host, $expected));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesUtm(array $context, array $condition): bool
    {
        $field = $condition['field'] ?? 'source';
        $utm = $context['utm'] ?? [];
        $actual = strtolower((string) ($utm[$field] ?? ''));
        $expected = strtolower((string) ($condition['value'] ?? ''));

        if ($actual === '') {
            return false;
        }

        return ($condition['operator'] ?? 'equals') === 'contains'
            ? str_contains($actual, $expected)
            : $actual === $expected;
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesDatetime(array $condition): bool
    {
        $timezone = $condition['timezone'] ?? config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);

        if (! empty($condition['days']) && ! in_array($now->isoWeekday(), $condition['days'], true)) {
            return false;
        }

        if (empty($condition['from']) || empty($condition['to'])) {
            return true;
        }

        $from = Carbon::parse($condition['from'], $timezone)->setDate($now->year, $now->month, $now->day);
        $to = Carbon::parse($condition['to'], $timezone)->setDate($now->year, $now->month, $now->day);

        if ($to->lessThan($from)) {
            // Overnight window (e.g. 22:00 -> 06:00): matches on either side of midnight.
            return $now->greaterThanOrEqualTo($from) || $now->lessThanOrEqualTo($to);
        }

        return $now->between($from, $to);
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesVisitCount(int $visitCount, array $condition): bool
    {
        $value = (int) ($condition['value'] ?? 0);

        return match ($condition['operator'] ?? 'gte') {
            'gt' => $visitCount > $value,
            'gte' => $visitCount >= $value,
            'lt' => $visitCount < $value,
            'lte' => $visitCount <= $value,
            'eq' => $visitCount === $value,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $condition
     */
    protected static function matchesQueryParam(array $context, array $condition): bool
    {
        $param = $condition['param'] ?? null;

        if (! $param) {
            return false;
        }

        $query = $context['query'] ?? [];
        $exists = array_key_exists($param, $query);
        $operator = $condition['operator'] ?? 'exists';

        return match ($operator) {
            'exists' => $exists,
            'not_exists' => ! $exists,
            'contains' => $exists && str_contains((string) $query[$param], (string) ($condition['value'] ?? '')),
            default => $exists && (string) $query[$param] === (string) ($condition['value'] ?? ''),
        };
    }
}
