<?php

namespace JeffersonGoncalves\LaravelShortUrl\Observers;

use Illuminate\Support\Facades\Auth;
use JeffersonGoncalves\LaravelShortUrl\Models\AuditLog;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

/**
 * "before" snapshots are stashed keyed by spl_object_id rather than on the
 * model itself — Eloquent's __set magic would otherwise route a plain
 * dynamic property straight into $attributes and try to save it as a column.
 */
class AuditLogObserver
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $before = [];

    public function created(ShortUrl $shortUrl): void
    {
        $this->record($shortUrl, 'created', null, $shortUrl->getAttributes());
    }

    public function updating(ShortUrl $shortUrl): void
    {
        static::$before[spl_object_id($shortUrl)] = $shortUrl->getOriginal();
    }

    public function updated(ShortUrl $shortUrl): void
    {
        $objectId = spl_object_id($shortUrl);
        $before = static::$before[$objectId] ?? [];
        unset(static::$before[$objectId]);

        if ($shortUrl->getChanges() === []) {
            return;
        }

        $this->record($shortUrl, 'updated', $before, $shortUrl->getChanges());
    }

    public function deleted(ShortUrl $shortUrl): void
    {
        $this->record($shortUrl, 'deleted', $shortUrl->getOriginal(), null);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    protected function record(ShortUrl $shortUrl, string $event, ?array $before, ?array $after): void
    {
        if (! config('short-url.audit.enabled', true)) {
            return;
        }

        AuditLog::query()->create([
            'short_url_id' => $shortUrl->id,
            'user_id' => Auth::id(),
            'event' => $event,
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>|null
     */
    protected function redact(?array $attributes): ?array
    {
        if ($attributes === null) {
            return null;
        }

        unset($attributes['password_hash']);

        return $attributes;
    }
}
