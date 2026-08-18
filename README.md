<div class="filament-hidden">

![Laravel Short URL](https://raw.githubusercontent.com/jeffersongoncalves/laravel-short-url/master/art/jeffersongoncalves-laravel-short-url.png)

</div>

# Laravel Short URL

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-short-url/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-short-url/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)

Headless URL-shortening engine for Laravel. Zero dependency on Filament — works standalone in any Laravel app via its Facade, REST API, or console commands.

## Why this package

- **High throughput.** The redirect pipeline is a chain of independent, testable stages (`Illuminate\Pipeline`), with the resolved link cached and analytics writes made asynchronous — no external integration failure (GeoIP, Safe Browsing, VPN detection, webhooks) can ever break a redirect.
- **Contract-driven.** Every swappable piece — analytics driver, conversion API dispatcher, DNS verifier, QR code builder, Safe Browsing checker, VPN detector — is an interface under `src/Contracts/`, with a default implementation and extensible registries (`AnalyticsDriverRegistry`, `DeepLinkRegistry`, `PixelProviderRegistry`, `FilterTypeRegistry`, `ImporterDriverRegistry`).
- **Minimal dependencies.** Only `spatie/laravel-package-tools` and `illuminate/contracts` are required. GeoIP (MaxMind), QR codes (`endroid/qr-code`), multi-tenancy (`stancl/tenancy`) and Redis (`predis/predis`) are all optional — the package works perfectly without them, each integration guarded by `class_exists`/a feature flag.
- **Multi-language.** pt_BR, en and es ship out of the box — no hardcoded strings outside `resources/lang`.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require jeffersongoncalves/laravel-short-url
```

Publish config, migrations and translations:

```bash
php artisan vendor:publish --tag="short-url-config"
php artisan vendor:publish --tag="short-url-migrations"
php artisan vendor:publish --tag="short-url-translations"
php artisan migrate
```

## Quick usage

```php
use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;

// Create
$link = ShortUrl::create(['destination_url' => 'https://example.com/product']);

// Fluent
$link = ShortUrl::destination('https://example.com/product')
    ->key('promo25')
    ->expiresAt(now()->addDays(30))
    ->maxVisits(1000)
    ->password('secret')
    ->create();

// Resolve
$link = ShortUrl::resolve('promo25');

// The ready-to-share URL — custom domain when set, otherwise the app's own host
$link->fullUrl(); // https://short.test/promo25
```

Redirecting itself needs no extra code: any request to `GET /{urlKey}` already flows through the full pipeline.

## Campaign tagging (UTM)

Every link can carry its own `utm_source`/`utm_medium`/`utm_campaign`/`utm_term`/`utm_content` — set directly, or from a reusable, tenant-scoped `UtmTemplate` ("campaign"):

```php
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;

$campaign = UtmTemplate::create(['name' => 'Spring SMS', 'utm_medium' => 'sms', 'utm_campaign' => 'spring-sale']);

$link = ShortUrl::destination('https://example.com/product')
    ->utmTemplate($campaign->id)   // fills in unset utm_* fields from the template
    ->utm(['utm_source' => 'agent-42']) // explicit values always win over the template
    ->create();
```

These values are attached to the destination URL on redirect (see `strip_utm_from_destination` to drop the click's own incoming `utm_*` first) and become the default attribution recorded on the visit whenever the click itself carries no `utm_*` of its own — so a link generated specifically for SMS is still correctly attributed even if whoever forwards it doesn't append query params by hand.

Set `short-url.utm.required` (e.g. `['utm_medium']`) to make `ShortUrlManager` reject creating — or updating — a link that doesn't declare those fields, directly or via a template. Enforced everywhere a link is created (facade, builder, REST API, CSV/Bitly import), not just at the API layer.

## Destination types

`destination_type` is one of `single`, `split`, or `rules`:

```php
// Weighted A/B rotation
ShortUrl::create([
    'destination_url' => 'https://example.com/base', // fallback
    'destination_type' => 'split',
    'rotation_variants' => [
        ['url' => 'https://a.test', 'weight' => 50, 'label' => 'A'],
        ['url' => 'https://b.test', 'weight' => 50, 'label' => 'B'],
    ],
]);

// Conditional targeting, evaluated per request
ShortUrl::create([
    'destination_url' => 'https://example.com/base', // used when no rule matches
    'destination_type' => 'rules',
    'targeting_rules' => [
        [
            'conditions' => [
                ['type' => 'country', 'value' => 'FR'],
                ['type' => 'device', 'value' => 'mobile'],
            ],
            'destination' => 'https://example.com/france-mobile',
        ],
    ],
]);
```

A condition's `type` can be `device`, `platform`, `browser`, `country`, `language`, `referer`, `utm_source`/`utm_medium`/`utm_campaign`, a date/time window, `visit_count`, `vpn`, or `bot`. Conditions default to AND; wrap a group in `['or' => [...]]` for OR logic. A rule's `destination` can itself be a nested `split` array to combine targeting with rotation, and rotation picks are evaluated with statistical-significance tracking (Z-test) so you can tell when a split has a real winner.

## The redirect pipeline

```
ResolveHost → RateLimit → ResolveShortUrl(cache) → DetectBot → DetectVpnProxy
→ CheckAvailability → RequirePassword → ShowWarning → ResolveDestination
→ BuildFinalUrl → RenderInterstitial → Respond → DispatchTracking
```

Each stage can short-circuit by returning a `Response` directly (wrong password, destination warning, expired link, blocked VPN, plan limit). The resolved link is cached (`{host}:{key}`) and invalidated automatically on `saved`/`deleted`.

## Feature overview

| Area | Description |
| --- | --- |
| **Redirecting** | Configurable Base62 keys, blacklist, uniqueness per domain, `301\|302\|307\|308`, `single_use`, `max_visits`, expiration with a fallback redirect. |
| **Analytics** | Asynchronous visit tracking (`TrackShortUrlVisitJob`), fast-path UA parsing, GeoIP (CDN headers / MaxMind / ip-api), bot detection, IP anonymization (IPv4 /24, IPv6 /48), daily aggregation with configurable retention. `StatsAggregator` breaks visits down by UTM source/medium/campaign, device, browser, OS, country, referer, and more. |
| **Targeting** | Nested `and\|or` rules by device, platform, browser, country, language, referer, UTM, date/time window, visit count, VPN, bot. Weighted A/B rotation with statistical significance (Z-test). |
| **Custom domains** | DNS verification (TXT/CNAME/A), per-domain routing, wildcard support, root redirect. |
| **Security** | Bcrypt password protection, signed-token warning page, Google Safe Browsing (sync or async blocking), VPN/proxy detection (flag or 403 block), rate limiting, full audit trail (before/after). |
| **Compliance** | Configurable retention (package-wide or per tenant plan), per-subject data export/deletion (LGPD/GDPR), analytics-only mode (no PII stored). |
| **REST API** | `/api/short-url/v1` (disabled by default), API-key auth with abilities, per-key rate limiting, link CRUD (accepts and returns `custom_domain_id`, `utm_*`, `utm_template_id`, and a ready-to-use `short_url`), bulk create (up to 500), stats, visits, domains, webhooks, conversions. |
| **Webhooks** | HMAC-SHA256 + anti-replay timestamp, retries at 10s/60s/300s, manual replay, auto-disable after consecutive failures. |
| **External analytics** | GA4, Plausible, PostHog, Matomo, Umami, Mixpanel, and Segment built in; `AnalyticsDriverRegistry::extend()` to add any other provider. |
| **Conversion tracking** | Server-to-server forwarding to Meta CAPI, Google Enhanced Conversions, TikTok Events API, and LinkedIn CAPI on `POST /conversions`. |
| **Alerts** | Z-score anomaly detection against a 7-day baseline, notifications via mail, database, broadcast, Slack, Discord, Telegram, Teams. |
| **QR codes** | SVG/PNG/PDF/EPS export (via the optional `endroid/qr-code`), scan tracking (`?source=qr`). |
| **Deep links & pixels** | Mobile app opening via custom URL scheme, 10 pre-registered apps, optional AASA/assetlinks serving, retargeting pixels (Meta, Google Ads, TikTok, GA4) with an optional consent banner. |
| **Organization** | Hierarchical folders, tags, reusable UTM templates ("campaigns"), archiving. |
| **Import/Export** | Built-in CSV importer, Bitly API v4 as the reference per-provider importer, CSV export via the API. |
| **ClickHouse** | Alternative `VisitRepository` driver over ClickHouse's native HTTP interface — same contract, no client library dependency. |
| **Multi-tenancy** | Fully feature-flagged. Auto-scoped via `stancl/tenancy` when installed, or a manual config override. Configurable plan limits (`links_per_month`, `domains`, `retention_days`). |
| **Link-in-bio** | Public pages at `/bio/{handle}` with blocks (link, text, image, video) and per-block click tracking. |

Recording a conversion always persists it locally, then optionally forwards it server-to-server based on `short-url.conversions.driver`:

```bash
curl -X POST https://your-app.test/api/short-url/v1/conversions \
  -H "Authorization: Bearer {api-key}" \
  -d '{"url_key": "promo25", "event_name": "purchase", "value": 49.90, "currency": "USD"}'
```

## Configuration

Every option is documented inline in `config/short-url.php`. Main groups:

`table_prefix`, `route`, `key`, `redirect`, `cache`, `tracking` (includes `clickhouse`), `domains`, `branding`, `security` (password, warning, rate limit, VPN, safe browsing), `compliance`, `audit`, `api`, `webhooks`, `analytics`, `conversions`, `alerts`, `notifications`, `qr`, `deep_links`, `pixels`, `importers`, `tenancy`, `bio`.

Settings can also be read/written at runtime via `Contracts\SettingsRepository`, with a declarative schema (`schema()`) for building dynamic forms in the UI plugin.

## Artisan commands

All self-register with the scheduler (`packageBooted()`), respecting their config toggles:

| Command | Frequency |
| --- | --- |
| `short-url:sync-counters` | every minute (when counter buffering is on) |
| `short-url:aggregate-and-prune` | daily at 02:00 |
| `short-url:verify-domains` | every 6h |
| `short-url:check-safe-browsing` | daily |
| `short-url:detect-anomalies` | hourly |
| `short-url:send-scheduled-reports` | daily |
| `short-url:prune-webhook-deliveries` | weekly |
| `short-url:import {driver} {source}` | manual |

`aggregate-and-prune` prunes each tenant's visit rows against its own plan `retention_days` when multi-tenancy is enabled, falling back to the package-wide `short-url.tracking.retention_days` otherwise.

## Public surface (contract with the UI plugin)

```php
ShortUrl::create(array $attributes): ShortUrlModel
ShortUrl::destination(string $url): ShortUrlBuilder
ShortUrl::resolve(string $key, ?string $host = null): ?ShortUrlModel

// ShortUrlModel
$shortUrl->fullUrl(): string // ready-to-share link (custom domain or app host)

// ShortUrlBuilder, in addition to the setters shown above
->customDomain(?int $customDomainId)
->utmTemplate(int $utmTemplateId)
->utm(array $attributes) // utm_source, utm_medium, utm_campaign, utm_term, utm_content

// src/Contracts/
VisitRepository, GeoIpDriver, VpnDetectionDriver, AnalyticsDriver,
SafeBrowsingChecker, QrCodeBuilder, StatsAggregator, TargetingResolver,
DnsVerifier, SettingsRepository, WebhookDispatcher, ImporterDriver,
ConversionApiDispatcher

// src/Registries/
FilterTypeRegistry, AnalyticsDriverRegistry, DeepLinkRegistry,
PixelProviderRegistry, ImporterDriverRegistry
```

## Testing

```bash
composer test     # Pest
composer analyse  # PHPStan (Larastan) level 6
composer format   # Pint
```

CI runs against PHP 8.4 / Laravel 13 on SQLite, MySQL, and PostgreSQL.

## AI-assisted development

This package ships a [Laravel Boost](https://github.com/laravel/boost) skill (`resources/boost/skills/short-url-development/`) and guideline (`resources/boost/guidelines/core.blade.php`) — if your project uses Boost, an AI assistant picks these up automatically and already knows the facade, contracts, destination types, campaign tagging, and conventions above.

## Security

Found a security vulnerability? See [SECURITY.md](.github/SECURITY.md).

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All contributors](../../contributors)

## License

MIT. See [LICENSE.md](LICENSE.md) for more information.
