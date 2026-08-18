<?php

namespace JeffersonGoncalves\LaravelShortUrl;

use DateTimeInterface;
use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl as ShortUrlModel;

class ShortUrlBuilder
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes;

    public function __construct(string $url)
    {
        $this->attributes = ['destination_url' => $url];
    }

    public function key(?string $key): static
    {
        $this->attributes['url_key'] = $key;

        return $this;
    }

    public function title(?string $title): static
    {
        $this->attributes['title'] = $title;

        return $this;
    }

    public function notes(?string $notes): static
    {
        $this->attributes['notes'] = $notes;

        return $this;
    }

    public function expiresAt(DateTimeInterface|string|null $date): static
    {
        $this->attributes['expires_at'] = $date;

        return $this;
    }

    public function maxVisits(?int $maxVisits): static
    {
        $this->attributes['max_visits'] = $maxVisits;

        return $this;
    }

    public function enabled(bool $enabled = true): static
    {
        $this->attributes['is_enabled'] = $enabled;

        return $this;
    }

    public function redirectStatusCode(int $code): static
    {
        $this->attributes['redirect_status_code'] = $code;

        return $this;
    }

    public function singleUse(bool $singleUse = true): static
    {
        $this->attributes['single_use'] = $singleUse;

        return $this;
    }

    public function forwardQueryParams(bool $forward = true): static
    {
        $this->attributes['forward_query_params'] = $forward;

        return $this;
    }

    public function password(?string $plain): static
    {
        $this->attributes['password_hash'] = $plain === null ? null : Hash::make($plain);

        return $this;
    }

    public function create(): ShortUrlModel
    {
        return app(ShortUrlManager::class)->create($this->attributes);
    }
}
