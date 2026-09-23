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
        // Only the stored row is cached, wrapped so a miss is cached too —
        // never the caller's default, which would otherwise outlive a
        // config change and leak across callers passing different defaults.
        // cache.ttl stays config-only here: reading it through get() would be circular.
        $row = config('short-url.cache.enabled', true)
            ? Cache::remember($this->cacheKey($key), (int) config('short-url.cache.ttl', 3600), fn () => $this->read($key))
            : $this->read($key);

        return array_key_exists('value', $row) ? $row['value'] : $default;
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
                'default' => (int) config('short-url.redirect.default_status_code', 302),
                'label' => trans('short-url::settings.redirect_status_code'),
                'group' => 'redirect',
                'rules' => ['integer', 'in:301,302,307,308'],
            ],
            'key.length' => [
                'type' => 'integer',
                'default' => (int) config('short-url.key.length', 7),
                'label' => trans('short-url::settings.key_length'),
                'group' => 'keys',
                'rules' => ['integer', 'min:4', 'max:32'],
            ],
            'cache.ttl' => [
                'type' => 'integer',
                'default' => (int) config('short-url.cache.ttl', 3600),
                'label' => trans('short-url::settings.cache_ttl'),
                'group' => 'cache',
                'rules' => ['integer', 'min:0'],
            ],
        ];
    }

    /**
     * @return array{value?: mixed}
     */
    protected function read(string $key): array
    {
        $row = DB::table($this->table())->where('key', $this->scopedKey($key))->first();

        return $row ? ['value' => json_decode((string) $row->value, true)] : [];
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
        // "settings.v2": entries now hold the wrapped row, not the raw value.
        return config('short-url.cache.prefix', 'short_url').':settings.v2:'.$this->scopedKey($key);
    }
}
