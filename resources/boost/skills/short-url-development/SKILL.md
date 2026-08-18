---
name: short-url-development
description: Build URL shortening, link analytics, and redirect features using the jeffersongoncalves/laravel-short-url package
---

# Laravel Short URL Development

## When to use this skill

Use this skill when implementing short link creation, redirects, click analytics, A/B/split testing on destinations, targeting rules, custom domains, QR codes, webhooks, conversion tracking, or link-in-bio pages in a Laravel application using the `jeffersongoncalves/laravel-short-url` package. The package is headless — no Filament dependency, no bundled admin UI — so every feature here is reached through the `ShortUrl` facade, its contracts, the REST API, or console commands.

## Setup

```bash
composer require jeffersongoncalves/laravel-short-url
php artisan vendor:publish --tag="short-url-config"
php artisan vendor:publish --tag="short-url-migrations"
php artisan vendor:publish --tag="short-url-translations"
php artisan migrate
```

All package tables use the `short_url_` prefix (configurable via `table_prefix`). The redirect route (`GET /{urlKey}`) registers at the application root by default; set `short-url.route.prefix` or `short-url.route.fallback` to change that.

## Creating and resolving links

```php
use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;

// Plain array
$link = ShortUrl::create(['destination_url' => 'https://example.com/product']);

// Fluent builder — ShortUrlBuilder
$link = ShortUrl::destination('https://example.com/product')
    ->key('promo25')                       // explicit key, otherwise Base62-generated
    ->title('Spring promo')
    ->expiresAt(now()->addDays(30))
    ->maxVisits(1000)
    ->singleUse()                          // disables after first visit
    ->password('secret')                   // bcrypt-hashed, never stored plain
    ->redirectStatusCode(301)
    ->forwardQueryParams()
    ->create();

// Resolve by key (used internally by the redirect pipeline too)
$link = ShortUrl::resolve('promo25');

// Ready-to-share URL — custom domain when set, otherwise the app's own host
$link->fullUrl();
```

Redirecting itself needs no controller — any request to `GET /{urlKey}` already flows through the full pipeline. Don't hand-roll a redirect route; extend the pipeline stages instead (see Contracts below) if you need custom behavior.

## Campaign tagging (UTM)

Every `ShortUrl` has its own `utm_source`/`utm_medium`/`utm_campaign`/`utm_term`/`utm_content`, plus a `custom_domain_id`. Both are settable directly or via a reusable `UtmTemplate` (tenant-scoped — the closest thing this package has to a "campaign"):

@verbatim
<code-snippet name="Tagging a link via a UTM template" lang="php">
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;

$campaign = UtmTemplate::create(['name' => 'Spring SMS', 'utm_medium' => 'sms']);

$link = ShortUrl::destination('https://example.com/product')
    ->utmTemplate($campaign->id)          // fills in unset utm_* fields
    ->utm(['utm_source' => 'agent-42'])   // explicit values always win
    ->customDomain($domain->id)
    ->create();
</code-snippet>
@endverbatim

These values get attached to the destination URL on redirect (`BuildFinalUrl` stage — `strip_utm_from_destination` drops the click's own incoming `utm_*` first if you want the link's tag to be the only one that lands), and become the default attribution on the recorded `Visit` whenever the click's own query string doesn't specify `utm_*` — so a link generated for one specific channel is still correctly attributed even if the person sharing it doesn't append query params.

Set `short-url.utm.required` (e.g. `['utm_medium']`) to make `ShortUrlManager::create()`/`update()` reject a link that doesn't declare those fields — directly, or via a template. This is enforced once, in the manager, so it applies uniformly across the facade, builder, REST API, and every importer (`CsvImporterDriver`, `BitlyImporterDriver`) — never re-implement this check at a call site.

## Destination types

`ShortUrl::destination_type` is one of `single`, `split`, or `rules`.

@verbatim
<code-snippet name="Split (weighted A/B) destination" lang="php">
ShortUrl::create([
    'destination_url' => 'https://example.com/base', // fallback
    'destination_type' => 'split',
    'rotation_variants' => [
        ['url' => 'https://a.test', 'weight' => 50, 'label' => 'A'],
        ['url' => 'https://b.test', 'weight' => 50, 'label' => 'B'],
    ],
]);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Rules (conditional targeting) destination" lang="php">
ShortUrl::create([
    'destination_url' => 'https://example.com/base', // used when no rule matches
    'destination_type' => 'rules',
    'targeting_rules' => [
        [
            'conditions' => [
                ['type' => 'country', 'value' => 'FR'],
                ['type' => 'device', 'value' => 'mobile'],
            ],
            // conditions default to AND; wrap in ['or' => [...]] for OR groups
            'destination' => 'https://example.com/france-mobile',
        ],
    ],
]);
</code-snippet>
@endverbatim

Condition `type` accepts: `device`, `platform`, `browser`, `country`, `language`, `referer`, `utm_source`/`utm_medium`/`utm_campaign`, a date/time window, `visit_count`, `vpn`, `bot`. A rule's `destination` can itself be a nested `split` array for combined targeting + rotation. `ShortUrl::resolve()` never evaluates rules — that happens inside the redirect pipeline's `ResolveDestination` stage against the live request; call `app(TargetingResolver::class)->resolve($shortUrl, $request)` directly if you need the same resolution outside a request.

## The redirect pipeline

```
ResolveHost → RateLimit → ResolveShortUrl(cache) → DetectBot → DetectVpnProxy
→ CheckAvailability → RequirePassword → ShowWarning → ResolveDestination
→ BuildFinalUrl → RenderInterstitial → Respond → DispatchTracking
```

Each stage is an invokable class under `src/Pipeline/Stages/` and can short-circuit by returning a `Response` directly (wrong password, expired link, blocked VPN, plan limit exceeded). Add a custom stage by extending `RedirectPipeline`'s stage array via the container — do not bypass the pipeline by writing your own redirect controller, since caching, tracking, and every security check live inside it.

## Analytics and tracking

Visits are recorded asynchronously (`TrackShortUrlVisitJob`) after the redirect response is sent — tracking failures never block or slow a redirect. Query aggregated stats through `Contracts\StatsAggregator` or the REST API rather than querying `short_url_visits` directly, since raw rows get pruned by `short-url:aggregate-and-prune` after being folded into `short_url_daily_stats`.

```php
use JeffersonGoncalves\LaravelShortUrl\Contracts\StatsAggregator;

$stats = app(StatsAggregator::class)->forShortUrl($shortUrl, from: now()->subDays(7), to: now());
```

External forwarding is config-gated per provider under `short-url.analytics.*` (`enabled` + credentials) — GA4, Plausible, PostHog, Matomo, Umami, Mixpanel, Segment ship built in. Add another provider with `AnalyticsDriverRegistry::extend()` in a service provider's `boot()`, implementing `Contracts\AnalyticsDriver::record(array $visit): void`.

## Conversion tracking

```php
// POST /api/short-url/v1/conversions (requires the conversions:write ability)
[
    'url_key' => 'promo25',       // or short_url_uuid
    'event_name' => 'purchase',
    'value' => 49.90,
    'currency' => 'USD',
]
```

Recording a conversion always persists it locally; `short-url.conversions.driver` (`meta`, `google`, `tiktok`, `linkedin`, or `none`) additionally forwards it server-to-server via `Contracts\ConversionApiDispatcher`. A dispatcher failure never throws back to the caller — it's caught and reported.

## Custom domains

Off by default (`short-url.domains.enabled`) so a plain install never pays for the extra lookup on every redirect. A domain must pass DNS verification (TXT/CNAME/A, via `Contracts\DnsVerifier`) before it routes traffic; `short-url:verify-domains` re-checks periodically and auto-disables a domain after `max_verification_failures` consecutive failures.

`custom_domain_id` on `short_url` is `NOT NULL` with sentinel `0` meaning "no custom domain" (not `NULL`) — this is deliberate: `NULL` is never equal to `NULL` in a composite unique index, which would silently break `unique(custom_domain_id, url_key)` for the common root-level case. Never write `custom_domain_id => null` directly; pass `null` through `ShortUrl::create()`/the builder and it's coerced to `0` for you.

## Security

- **Password protection**: `->password($plain)` bcrypt-hashes on the builder; the pipeline's `RequirePassword` stage renders a prompt and unlocks via a signed, session-scoped token.
- **Safe Browsing**: `short-url.security.safe_browsing` — sync (blocks the redirect) or async (`short-url:check-safe-browsing` flags links after the fact) via `Contracts\SafeBrowsingChecker`.
- **VPN/proxy detection**: `Contracts\VpnDetectionDriver` (`ip_api` or `proxycheck_io`), flag-only or 403-block via config.
- **Rate limiting**: per-IP, config-driven, applied in the pipeline's `RateLimit` stage.
- **Audit trail**: every create/update/delete on `ShortUrl` and `CustomDomain` is recorded with before/after diffs via `AuditLogObserver` — never disable this without checking `short-url.audit` first.

## Multi-tenancy

Entirely feature-flagged (`short-url.tenancy.enabled`), off by default and a complete no-op when off. When on, the tenant id resolves via `stancl/tenancy`'s `tenant()` helper if installed, otherwise `short-url.tenancy.current_tenant_id`. Per-plan limits (`links_per_month`, `domains`, `retention_days`) live in `short-url.tenancy.plans.*` and are resolved through `Tenancy\PlanLimits`; a plan is picked by a host-supplied `plan_resolver` Closure, defaulting to `"default"`. `short-url:aggregate-and-prune` applies each tenant's own `retention_days` when tenancy is enabled, falling back to the package-wide `short-url.tracking.retention_days` otherwise.

## Webhooks

```php
// POST /api/short-url/v1/webhooks
['url' => 'https://example.com/hook', 'events' => ['link.visited', 'conversion.recorded'], 'short_url_id' => null] // null = global (all links)
```

Deliveries are HMAC-SHA256 signed with an anti-replay timestamp header, retried at 10s/60s/300s, and auto-disabled after consecutive failures (manually replayable via the API). Dispatch a custom event yourself with `app(Contracts\WebhookDispatcher::class)->dispatch('custom.event', $payload, $shortUrl)`.

## Events

| Event | Properties |
|-------|-----------|
| `ShortUrlVisited` | `$shortUrl`, `$visit` |
| `ConversionRecorded` | `$conversion` |
| `AlertTriggered` | anomaly alert details (Z-score anomaly detection against a 7-day baseline) |

## Extending the package (contracts)

Every swappable piece is an interface under `src/Contracts/`, bound to a default implementation in `LaravelShortUrlServiceProvider`. Rebind in your own service provider to replace one, or use a registry's `extend()` to add alongside the defaults:

```php
VisitRepository, GeoIpDriver, VpnDetectionDriver, AnalyticsDriver,
SafeBrowsingChecker, QrCodeBuilder, StatsAggregator, TargetingResolver,
DnsVerifier, SettingsRepository, WebhookDispatcher, ImporterDriver,
ConversionApiDispatcher

// Registries (extend() instead of rebind — multiple drivers coexist)
FilterTypeRegistry, AnalyticsDriverRegistry, DeepLinkRegistry,
PixelProviderRegistry, ImporterDriverRegistry
```

`VisitRepository` also ships a ClickHouse driver (`short-url.tracking.driver = clickhouse`) — same contract, no client library dependency, talks to ClickHouse's native HTTP interface directly. Use it when visit volume outgrows a relational `short_url_visits` table.

## Artisan commands

| Command | Frequency |
| --- | --- |
| `short-url:sync-counters` | every minute (when `tracking.counter_buffering` is on) |
| `short-url:aggregate-and-prune` | daily at 02:00 — folds visits into daily stats, then prunes |
| `short-url:verify-domains` | every 6h |
| `short-url:check-safe-browsing` | daily |
| `short-url:detect-anomalies` | hourly |
| `short-url:send-scheduled-reports` | daily |
| `short-url:prune-webhook-deliveries` | weekly |
| `short-url:import {driver} {source}` | manual — `csv` ships in, `bitly` is the reference per-provider importer |

All self-register with the scheduler in `packageBooted()`, respecting their own config toggles — you don't need to add them to your `Kernel`/`bootstrap/app.php` schedule yourself.

## Testing your own code against this package

Use Pest with `RefreshDatabase` and Orchestra Testbench (see the package's own `tests/TestCase.php` for the pattern). On Postgres specifically, wrap any assertion that expects a `QueryException` (e.g. a uniqueness constraint) in `DB::transaction()` — Postgres aborts the whole enclosing transaction on any uncaught error until a rollback, which poisons whatever query `RefreshDatabase` runs next in the same test; `DB::transaction()` uses a `SAVEPOINT` instead and contains the failure.
