<?php

use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;
use JeffersonGoncalves\LaravelShortUrl\Models\Folder;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Models\Webhook;

it('does not scope queries when tenancy is disabled, even across differing tenant_id values', function () {
    config(['short-url.tenancy.enabled' => false]);

    ShortUrl::factory()->create(['tenant_id' => 1]);
    ShortUrl::factory()->create(['tenant_id' => 2]);

    expect(ShortUrl::query()->count())->toBe(2);
});

it('scopes queries to the current tenant when enabled', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 1]);

    ShortUrl::factory()->create(['tenant_id' => 1]);
    ShortUrl::factory()->create(['tenant_id' => 2]);

    expect(ShortUrl::query()->count())->toBe(1);
});

it('auto-fills tenant_id from the current tenant on create', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 7]);

    $shortUrl = ShortUrl::factory()->create();

    expect($shortUrl->tenant_id)->toBe(7);
});

it('scopes custom domains, folders and webhooks the same way', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 1]);

    CustomDomain::factory()->create(['tenant_id' => 1]);
    CustomDomain::factory()->create(['tenant_id' => 2]);
    Folder::factory()->create(['tenant_id' => 1]);
    Folder::factory()->create(['tenant_id' => 2]);
    Webhook::factory()->create(['tenant_id' => 1]);
    Webhook::factory()->create(['tenant_id' => 2]);

    expect(CustomDomain::query()->count())->toBe(1)
        ->and(Folder::query()->count())->toBe(1)
        ->and(Webhook::query()->count())->toBe(1);
});
