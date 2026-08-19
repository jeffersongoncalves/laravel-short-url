<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelShortUrl\Repositories\ClickHouseVisitRepository;

beforeEach(function () {
    config(['short-url.tracking.clickhouse.host' => 'clickhouse.test']);
});

it('throws a clear error when no host is configured', function () {
    config(['short-url.tracking.clickhouse.host' => null]);

    expect(fn () => (new ClickHouseVisitRepository)->store(['short_url_id' => 1]))
        ->toThrow(RuntimeException::class);
});

it('sends an insert statement when storing a visit', function () {
    Http::fake(['*clickhouse.test*' => Http::response('')]);

    (new ClickHouseVisitRepository)->store(['short_url_id' => 1, 'is_bot' => false]);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'INSERT INTO') && str_contains($request->body(), 'short_url_id'));
});

it('parses JSONEachRow rows from a query', function () {
    Http::fake(['*clickhouse.test*' => Http::response(
        '{"short_url_id":1,"country_code":"BR"}'."\n".'{"short_url_id":1,"country_code":"US"}'."\n"
    )]);

    $rows = (new ClickHouseVisitRepository)->query(1);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['country_code'])->toBe('BR');
});

it('builds an aggregate payload from multiple queries', function () {
    Http::fakeSequence()
        ->push('{"visits_count":5,"unique_visits_count":4,"bot_visits_count":0}')
        ->whenEmpty(Http::response(''));

    $result = (new ClickHouseVisitRepository)->aggregate(1, now()->subDay(), now());

    expect($result['visits_count'])->toBe(5)
        ->and($result['unique_visits_count'])->toBe(4)
        ->and($result)->toHaveKey('device_stats')
        ->and($result)->toHaveKey('hourly_stats');
});

it('builds an aggregate payload across multiple short_url_ids', function () {
    Http::fakeSequence()
        ->push('{"visits_count":9,"unique_visits_count":7,"bot_visits_count":1}')
        ->whenEmpty(Http::response(''));

    $result = (new ClickHouseVisitRepository)->aggregateMany([1, 2, 3], now()->subDay(), now());

    expect($result['visits_count'])->toBe(9);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'short_url_id IN (1,2,3)'));
});

it('returns empty totals from aggregateMany([]) without hitting clickhouse', function () {
    Http::fake();

    $result = (new ClickHouseVisitRepository)->aggregateMany([], now()->subDay(), now());

    expect($result['visits_count'])->toBe(0);
    Http::assertNothingSent();
});

it('prunes and returns the number of rows removed', function () {
    Http::fakeSequence()
        ->push('{"c":3}')
        ->push('');

    $pruned = (new ClickHouseVisitRepository)->prune(now()->subDays(30));

    expect($pruned)->toBe(3);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'ALTER TABLE') && str_contains($request->body(), 'DELETE WHERE'));
});

it('scopes the prune condition to a tenant when given', function () {
    Http::fakeSequence()
        ->push('{"c":1}')
        ->push('');

    (new ClickHouseVisitRepository)->prune(now()->subDays(30), 7);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'tenant_id = 7'));
});

it('throws when the clickhouse http request fails', function () {
    Http::fake(['*clickhouse.test*' => Http::response('boom', 500)]);

    expect(fn () => (new ClickHouseVisitRepository)->query(1))->toThrow(RuntimeException::class);
});
