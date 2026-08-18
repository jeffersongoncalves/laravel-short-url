<?php

namespace JeffersonGoncalves\LaravelShortUrl\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelShortUrl\Contracts\SettingsRepository;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\TenantContext;

class DatabaseSettingsRepository implements SettingsRepository
{
    public function __construct(protected TenantContext $tenants) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if (! config('short-url.cache.enabled', true)) {
            return $this->read($key, $default);
        }

        return Cache::remember(
            $this->cacheKey($key),
            (int) config('short-url.cache.ttl', 3600),
            fn () => $this->read($key, $default)
        );
    }

    public function set(string $key, mixed $value): void
    {
        $table = $this->table();
        $encoded = json_encode($value);
        $storageKey = $this->scopedKey($key);
        $tenantId = $this->tenants->currentId();

        if (DB::table($table)->where('key', $storageKey)->exists()) {
            DB::table($table)->where('key', $storageKey)->update([
                'value' => $encoded,
                'updated_at' => now(),
            ]);
        } else {
            DB::table($table)->insert([
                'key' => $storageKey,
                'value' => $encoded,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget($this->cacheKey($key));
    }

    public function forget(string $key): void
    {
        DB::table($this->table())->where('key', $this->scopedKey($key))->delete();

        Cache::forget($this->cacheKey($key));
    }

    public function schema(): array
    {
        return [
            'redirect.default_status_code' => [
                'type' => 'integer',
                'default' => 302,
                'label' => trans('short-url::settings.redirect_status_code'),
                'group' => 'redirect',
                'rules' => ['integer', 'in:301,302,307,308'],
            ],
            'key.length' => [
                'type' => 'integer',
                'default' => 7,
                'label' => trans('short-url::settings.key_length'),
                'group' => 'keys',
                'rules' => ['integer', 'min:4', 'max:32'],
            ],
            'cache.ttl' => [
                'type' => 'integer',
                'default' => 3600,
                'label' => trans('short-url::settings.cache_ttl'),
                'group' => 'cache',
                'rules' => ['integer', 'min:0'],
            ],
        ];
    }

    protected function read(string $key, mixed $default): mixed
    {
        $row = DB::table($this->table())->where('key', $this->scopedKey($key))->first();

        return $row ? json_decode((string) $row->value, true) : $default;
    }

    protected function table(): string
    {
        return config('short-url.table_prefix', 'short_url_').'settings';
    }

    /**
     * "key" is a plain global-unique column (no per-tenant DB constraint,
     * to stay portable across Postgres/MySQL/SQLite) — tenant scoping is
     * achieved by prefixing the tenant id directly into the stored key.
     */
    protected function scopedKey(string $key): string
    {
        $tenantId = $this->tenants->currentId();

        return $tenantId === null ? $key : "{$tenantId}:{$key}";
    }

    protected function cacheKey(string $key): string
    {
        return config('short-url.cache.prefix', 'short_url').':settings:'.$this->scopedKey($key);
    }
}
