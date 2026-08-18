<?php

use JeffersonGoncalves\LaravelShortUrl\Models\AuditLog;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

it('records a created audit entry', function () {
    $shortUrl = ShortUrl::factory()->create();

    $log = AuditLog::query()->where('short_url_id', $shortUrl->id)->where('event', 'created')->first();

    expect($log)->not->toBeNull()
        ->and($log->after['destination_url'])->toBe($shortUrl->destination_url);
});

it('records an updated audit entry with before and after', function () {
    $shortUrl = ShortUrl::factory()->create(['title' => 'Old title']);

    $shortUrl->update(['title' => 'New title']);

    $log = AuditLog::query()->where('short_url_id', $shortUrl->id)->where('event', 'updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->before['title'])->toBe('Old title')
        ->and($log->after['title'])->toBe('New title');
});

it('does not log an update with no actual changes', function () {
    $shortUrl = ShortUrl::factory()->create();
    AuditLog::query()->where('short_url_id', $shortUrl->id)->delete();

    $shortUrl->save();

    expect(AuditLog::query()->where('short_url_id', $shortUrl->id)->count())->toBe(0);
});

it('records a deleted audit entry', function () {
    $shortUrl = ShortUrl::factory()->create();
    $id = $shortUrl->id;

    $shortUrl->forceDelete();

    $log = AuditLog::query()->where('short_url_id', $id)->where('event', 'deleted')->first();

    expect($log)->not->toBeNull();
});

it('never stores the password hash in an audit log', function () {
    $shortUrl = ShortUrl::factory()->create(['password_hash' => 'super-secret-hash']);

    $log = AuditLog::query()->where('short_url_id', $shortUrl->id)->where('event', 'created')->first();

    expect($log->after)->not->toHaveKey('password_hash');
});

it('does not record anything when auditing is disabled', function () {
    config(['short-url.audit.enabled' => false]);

    $shortUrl = ShortUrl::factory()->create();

    expect(AuditLog::query()->where('short_url_id', $shortUrl->id)->count())->toBe(0);
});
