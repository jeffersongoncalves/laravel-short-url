<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Database\Factories\CustomDomainFactory;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $domain
 * @property bool $is_wildcard
 * @property string $verification_token
 * @property bool $is_verified
 * @property Carbon|null $verified_at
 * @property string|null $dns_record_type
 * @property Carbon|null $last_checked_at
 * @property int $failure_count
 * @property Carbon|null $disabled_at
 * @property string|null $root_redirect_url
 */
class CustomDomain extends Model
{
    /** @use HasFactory<CustomDomainFactory> */
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_wildcard' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'failure_count' => 'integer',
            'disabled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $domain): void {
            $domain->verification_token ??= 'short-url-verify-'.Str::random(32);
        });
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'custom_domains';
    }

    protected static function newFactory(): CustomDomainFactory
    {
        return CustomDomainFactory::new();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_verified', true)->whereNull('disabled_at');
    }

    /**
     * Matches the exact registered domain, or — for wildcard entries — any
     * subdomain of it (e.g. "links.acme.com" against a wildcard "acme.com").
     */
    public static function forHost(string $host): ?self
    {
        $host = strtolower($host);

        return static::query()
            ->active()
            ->get()
            ->sortByDesc(fn (self $domain) => strlen($domain->domain))
            ->first(fn (self $domain) => $host === strtolower($domain->domain)
                || ($domain->is_wildcard && str_ends_with($host, '.'.strtolower($domain->domain))));
    }
}
