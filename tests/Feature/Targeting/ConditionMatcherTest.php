<?php

use Illuminate\Support\Carbon;
use JeffersonGoncalves\LaravelShortUrl\Targeting\ConditionMatcher;

it('matches device type with an "in" operator', function () {
    $context = ['device_type' => 'mobile'];

    expect(ConditionMatcher::matches(['type' => 'device', 'value' => ['mobile', 'tablet']], $context))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'device', 'value' => 'desktop'], $context))->toBeFalse();
});

it('matches with a not_in operator', function () {
    $context = ['country_code' => 'US'];

    expect(ConditionMatcher::matches(['type' => 'country', 'operator' => 'not_in', 'value' => ['BR']], $context))->toBeTrue();
});

it('matches language via exact then primary-subtag fallback', function () {
    expect(ConditionMatcher::matches(['type' => 'language', 'value' => 'pt-BR'], ['browser_language' => 'pt-BR']))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'language', 'value' => 'pt'], ['browser_language' => 'pt-BR']))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'language', 'value' => 'en'], ['browser_language' => 'pt-BR']))->toBeFalse();
});

it('matches referer by type', function () {
    $context = ['referer_type' => 'social', 'referer_host' => 'facebook.com'];

    expect(ConditionMatcher::matches(['type' => 'referer', 'operator' => 'type', 'value' => 'social'], $context))->toBeTrue();
});

it('matches referer host by contains', function () {
    $context = ['referer_host' => 'www.google.com'];

    expect(ConditionMatcher::matches(['type' => 'referer', 'operator' => 'contains', 'value' => 'google'], $context))->toBeTrue();
});

it('matches a utm field', function () {
    $context = ['utm' => ['source' => 'newsletter']];

    expect(ConditionMatcher::matches(['type' => 'utm', 'field' => 'source', 'value' => 'newsletter'], $context))->toBeTrue();
});

it('matches a datetime window within the same day', function () {
    $condition = ['type' => 'datetime', 'from' => '00:00', 'to' => '23:59', 'timezone' => 'UTC'];

    expect(ConditionMatcher::matches($condition, []))->toBeTrue();
});

it('matches an overnight datetime window that wraps past midnight', function () {
    $condition = ['type' => 'datetime', 'from' => '20:00', 'to' => '04:00', 'timezone' => 'UTC'];

    Carbon::setTestNow(Carbon::parse('2026-01-01 23:00:00', 'UTC'));
    expect(ConditionMatcher::matches($condition, []))->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-01-01 02:00:00', 'UTC'));
    expect(ConditionMatcher::matches($condition, []))->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00', 'UTC'));
    expect(ConditionMatcher::matches($condition, []))->toBeFalse();

    Carbon::setTestNow();
});

it('respects the weekday restriction on a datetime condition', function () {
    $wrongDay = array_diff(range(1, 7), [now('UTC')->isoWeekday()]);

    expect(ConditionMatcher::matches(['type' => 'datetime', 'days' => $wrongDay], []))->toBeFalse()
        ->and(ConditionMatcher::matches(['type' => 'datetime', 'days' => [now('UTC')->isoWeekday()]], []))->toBeTrue();
});

it('matches visit_count with comparison operators', function () {
    expect(ConditionMatcher::matches(['type' => 'visit_count', 'operator' => 'gte', 'value' => 5], ['visit_count' => 5]))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'visit_count', 'operator' => 'lt', 'value' => 5], ['visit_count' => 5]))->toBeFalse();
});

it('matches is_bot and is_vpn boolean flags', function () {
    expect(ConditionMatcher::matches(['type' => 'is_bot', 'value' => true], ['is_bot' => true]))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'is_vpn', 'value' => true], ['is_vpn' => false]))->toBeFalse();
});

it('matches a query_param condition', function () {
    $context = ['query' => ['ref' => 'abc']];

    expect(ConditionMatcher::matches(['type' => 'query_param', 'param' => 'ref', 'operator' => 'exists'], $context))->toBeTrue()
        ->and(ConditionMatcher::matches(['type' => 'query_param', 'param' => 'missing', 'operator' => 'exists'], $context))->toBeFalse()
        ->and(ConditionMatcher::matches(['type' => 'query_param', 'param' => 'ref', 'operator' => 'equals', 'value' => 'abc'], $context))->toBeTrue();
});

it('never matches an unknown condition type', function () {
    expect(ConditionMatcher::matches(['type' => 'unknown'], []))->toBeFalse();
});
