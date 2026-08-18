<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;

it('keeps settings unscoped when tenancy is disabled', function () {
    config(['short-url.tenancy.enabled' => false]);
    $settings = app(SettingsRepository::class);

    $settings->set('brand_name', 'Acme');

    $prefix = config('short-url.table_prefix', 'short_url_');
    expect(DB::table($prefix.'settings')->where('key', 'brand_name')->exists())->toBeTrue();
});

it('isolates the same setting key between two tenants', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 1]);
    $settings = app(SettingsRepository::class);
    $settings->set('brand_name', 'Tenant One');

    config(['short-url.tenancy.current_tenant_id' => 2]);
    expect($settings->get('brand_name'))->toBeNull();

    $settings->set('brand_name', 'Tenant Two');

    config(['short-url.tenancy.current_tenant_id' => 1]);
    expect($settings->get('brand_name'))->toBe('Tenant One');

    config(['short-url.tenancy.current_tenant_id' => 2]);
    expect($settings->get('brand_name'))->toBe('Tenant Two');
});

it('forgets only the current tenant scoped setting', function () {
    config(['short-url.tenancy.enabled' => true, 'short-url.tenancy.current_tenant_id' => 1]);
    $settings = app(SettingsRepository::class);
    $settings->set('brand_name', 'Tenant One');

    config(['short-url.tenancy.current_tenant_id' => 2]);
    $settings->set('brand_name', 'Tenant Two');

    config(['short-url.tenancy.current_tenant_id' => 1]);
    $settings->forget('brand_name');

    expect($settings->get('brand_name'))->toBeNull();

    config(['short-url.tenancy.current_tenant_id' => 2]);
    expect($settings->get('brand_name'))->toBe('Tenant Two');
});
