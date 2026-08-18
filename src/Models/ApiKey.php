<?php

namespace JeffersonGoncalves\LaravelShortUrl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JeffersonGoncalves\LaravelShortUrl\Tenancy\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $key_prefix
 * @property string $key_hash
 * @property array<int, string> $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 */
class ApiKey extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('short-url.table_prefix', 'short_url_').'api_keys';
    }

    /**
     * @param  array<int, string>  $abilities
     * @return array{key: ApiKey, token: string}
     */
    public static function generate(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
    {
        $token = Str::random(48);

        $key = static::query()->create([
            'name' => $name,
            'key_prefix' => substr($token, 0, 8),
            'key_hash' => hash('sha256', $token),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return ['key' => $key, 'token' => $token];
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function can(mixed $ability, array $arguments = []): bool
    {
        return in_array('*', $this->abilities, true) || in_array($ability, $this->abilities, true);
    }

    public function isValid(): bool
    {
        return ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
