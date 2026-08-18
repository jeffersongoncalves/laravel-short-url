<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property int|null $team_id
 * @property int|null $user_id
 * @property int|null $folder_id
 * @property int|null $custom_domain_id
 * @property string $url_key
 * @property string $destination_url
 * @property string $destination_type
 * @property string|null $title
 * @property string|null $notes
 * @property string|null $internal_ref
 * @property bool $is_enabled
 * @property int $redirect_status_code
 * @property bool $single_use
 * @property bool $forward_query_params
 * @property bool $strip_utm_from_destination
 * @property int|null $max_visits
 * @property int $total_visits
 * @property int $unique_visits
 * @property int $qr_scans
 * @property int $bot_visits
 * @property Carbon|null $activated_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $deactivated_at
 * @property string|null $expiration_redirect_url
 * @property string|null $password_hash
 * @property string|null $password_hint
 * @property bool $show_warning_page
 * @property string|null $warning_message
 * @property bool $auto_open_app_mobile
 * @property string|null $app_scheme_override
 * @property string|null $ga_tracking_id
 * @property string|null $ga_api_secret_override
 * @property string|null $webhook_url
 * @property string|null $webhook_secret
 * @property array<string, mixed>|null $targeting_rules
 * @property array<string, mixed>|null $rotation_variants
 * @property array<string, mixed>|null $geo_fence
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image_path
 * @property array<string, mixed>|null $qr_design
 * @property string|null $safe_browsing_status
 * @property Carbon|null $safe_browsing_checked_at
 * @property bool $track_visits
 * @property bool $track_ip_address
 * @property bool $track_browser
 * @property bool $track_browser_version
 * @property bool $track_operating_system
 * @property bool $track_operating_system_version
 * @property bool $track_device_type
 * @property bool $track_referer_url
 * @property bool $track_browser_language
 * @property Carbon|null $last_visited_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ShortUrl extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'single_use' => 'boolean',
            'forward_query_params' => 'boolean',
            'strip_utm_from_destination' => 'boolean',
            'show_warning_page' => 'boolean',
            'auto_open_app_mobile' => 'boolean',
            'track_visits' => 'boolean',
            'track_ip_address' => 'boolean',
            'track_browser' => 'boolean',
            'track_browser_version' => 'boolean',
            'track_operating_system' => 'boolean',
            'track_operating_system_version' => 'boolean',
            'track_device_type' => 'boolean',
            'track_referer_url' => 'boolean',
            'track_browser_language' => 'boolean',
            'redirect_status_code' => 'integer',
            'max_visits' => 'integer',
            'total_visits' => 'integer',
            'unique_visits' => 'integer',
            'qr_scans' => 'integer',
            'bot_visits' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'safe_browsing_checked_at' => 'datetime',
            'last_visited_at' => 'datetime',
            'archived_at' => 'datetime',
            'targeting_rules' => 'array',
            'rotation_variants' => 'array',
            'geo_fence' => 'array',
            'qr_design' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $shortUrl): void {
            $shortUrl->uuid ??= (string) Str::uuid();
        });
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'urls';
    }

    public function getRouteKeyName(): string
    {
        return 'url_key';
    }

    /**
     * Resolves a key at the app's own domain (custom_domain_id null) unless
     * a verified custom domain id is given, in which case the key is
     * scoped to that domain instead.
     */
    public static function findByKey(string $urlKey, ?int $customDomainId = null): ?self
    {
        $query = static::query()->where('url_key', $urlKey);

        $customDomainId === null
            ? $query->whereNull('custom_domain_id')
            : $query->where('custom_domain_id', $customDomainId);

        return $query->first();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeEnabled($query): void
    {
        $query->where('is_enabled', true);
    }

    public function customDomain(): BelongsTo
    {
        return $this->belongsTo(CustomDomain::class, 'custom_domain_id');
    }

    /**
     * @return BelongsToMany<Pixel, $this>
     */
    public function pixels(): BelongsToMany
    {
        return $this->belongsToMany(
            Pixel::class,
            config('short-url.table_prefix', 'short_url_').'pixel_short_url'
        );
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            config('short-url.table_prefix', 'short_url_').'tag_short_url'
        );
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function unarchive(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
