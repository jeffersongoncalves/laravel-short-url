# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v4.5.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/5.0.0...v4.5.0) - 2026-09-12

Add config toggles to disable the package's self-registered schedules (aggregate-and-prune, detect-anomalies, send-scheduled-reports), plus a configurable time for aggregate-and-prune. Closes #26.

## [5.0.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.7...5.0.0) - 2026-09-11

### Breaking Changes

- Device/browser/OS parsing, bot detection, IP anonymization, GeoIP resolution, VPN/proxy/Tor detection, and the GDPR export/erasure internals now live in [`jeffersongoncalves/laravel-visitor-fingerprint`](https://github.com/jeffersongoncalves/laravel-visitor-fingerprint) instead of being duplicated in this package (#24).
- `Contracts\GeoIpDriver`, `Contracts\VpnDetectionDriver`, `Data\GeoLocation`, and `Data\ThreatResult` moved to the `JeffersonGoncalves\VisitorFingerprint` namespace. Rebind any custom implementation against the vendor's contracts instead of this package's own.
- Config keys moved to `config/visitor-fingerprint.php`: `short-url.tracking.geoip.*` → `visitor-fingerprint.geoip.*`, `short-url.tracking.trust_cdn_headers` (removed — selecting the `headers` GeoIP driver is itself the opt-in now), `short-url.tracking.ip_hash_salt` → `visitor-fingerprint.hash_salt`, `short-url.security.vpn_detection.{driver,cache_ttl,proxycheck_api_key}` → `visitor-fingerprint.vpn_detection.*`. `short-url.security.vpn_detection.mode` (off/flag/block) is unchanged — it's this package's own enforcement policy.
- `Compliance\PersonalDataService` keeps its existing `exportForIp()`/`forgetForIp()` API — it's now a thin wrapper over the vendor's `Compliance\PersonalDataExporter`, so most consumers need no code change there.

See #25 for the full diff and migration notes.

## [4.4.7](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.6...4.4.7) - 2026-09-11

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.6...4.4.7

## [4.4.6](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.5...4.4.6) - 2026-09-11

Fixed

- ShortUrl::fullUrl() now builds through the app's own URL generator
  (url()) instead of parsing config('app.url') by hand, so it agrees
  with URL::forceHttps() and trusted-proxy scheme detection like every
  other link the app mints. Previously it could silently mint http://
  links even when the app forces https everywhere else (e.g. non-local
  environments, behind Cloudflare/a load balancer).

## [4.4.5](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.4...4.4.5) - 2026-09-10

### What's Changed

* fix: give StatsAggregator/VisitRepository a real no-scope path for global stats by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/23

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.4...4.4.5

## [4.4.4](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.3...4.4.4) - 2026-09-10

### What's Changed

* test: round-trip every migration stub's up()/down() against a fresh schema by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/21

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.3...4.4.4

## [4.4.3](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.2...4.4.3) - 2026-09-10

### What's Changed

* fix: add date-led indexes for the global dashboard aggregation path by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/19

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.2...4.4.3

## [4.4.2](https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.1...4.4.2) - 2026-09-10

### What's Changed

* fix: push live stats aggregation to SQL GROUP BY instead of PHP collections by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/17

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/4.4.1...4.4.2

## [4.4.1](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.4.0...4.4.1) - 2026-09-09

resolveMany() now accepts an optional `$attributes` array merged into every newly-created row (e.g. `internal_ref`, a fixed `title`) — never applied to a row resolved from cache or an existing destination_url match.

## [v4.4.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.3.1...v4.4.0) - 2026-09-09

### What's Changed

* docs: add Buy Me a Coffee sponsor link by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/11
* docs: standardize README section structure by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/12
* chore: add GitHub Sponsors to FUNDING.yml by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/13
* feat: batch resolve/create for outbound URLs via ShortUrl::resolveMany() by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/15

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.3.1...v4.4.0

## [v4.3.1](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.3.0...v4.3.1) - 2026-09-04

### What's Changed

* fix: QrCodeGenerator targets endroid/qr-code v6's Builder API by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/10

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.3.0...v4.3.1

## [v4.3.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.2.0...v4.3.0) - 2026-09-04

### What's Changed

* feat: QR code generation for short urls by @jeffersongoncalves in https://github.com/jeffersongoncalves/laravel-short-url/pull/9

### New Contributors

* @jeffersongoncalves made their first contribution in https://github.com/jeffersongoncalves/laravel-short-url/pull/9

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.2.0...v4.3.0

## [v4.2.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.1.0...v4.2.0) - 2026-08-24

### Added

- `Contracts\CustomDomainResolver`: host apps with their own domain->tenant mapping (multi-tenant SaaS with per-account custom domains) can now bind an implementation in their own `ServiceProvider::register()` instead of duplicating domain registration into `short_url_custom_domains`. `Pipeline\Stages\ResolveHost` calls it before the package's own `CustomDomain::forHost()` lookup, whenever `domains.enabled` is true. See the README's "Custom domain resolution without short_url_custom_domains" section.

Closes #7

## [v4.1.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v4.0.0...v4.1.0) - 2026-08-24

### Fixed

- Postgres' `json` type has no equality operator, so any query that needs to deduplicate rows containing one of the package's JSON columns (`SELECT DISTINCT`, `GROUP BY`, `UNION`) failed with `SQLSTATE[42883]: could not identify an equality operator for type json`. Notably, filament-short-url's Pixels multi-select runs `SELECT DISTINCT` against `short_url_pixels`, so opening the Create/Edit Short URL form 500'd on Postgres.
- Every `json()` column across the package's migration stubs is now `jsonb()`: `short_urls`, `short_url_pixels`, `short_url_settings`, `short_url_daily_stats`, `short_url_audit_logs`, `short_url_conversions`, `short_url_alerts`. `jsonb` has an equality operator, is indexable, and is Postgres' own recommended type over `json`. MySQL/SQLite map both the same way, so this is safe on all three supported drivers.

#### Note for existing Postgres installs

This only affects fresh installs (published migrations are copied into your app and owned by it from then on). If you already migrated on Postgres, alter the columns yourself, e.g.:

```sql
ALTER TABLE short_url_pixels ALTER COLUMN config TYPE jsonb USING config::jsonb;














```
Repeat per affected column/table above.

Fixes #6

## [v4.0.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v3.1.0...v4.0.0) - 2026-08-23

### Breaking Changes

- `Contracts\TenantResolver` and `Contracts\PlanResolver` replace `short-url.tenancy.plan_resolver`. A plain config Closure can't survive `php artisan config:cache` — Laravel `var_export()`s the whole config array, which throws `LogicException: Your configuration files are not serializable.` on any Closure. Bindings live in code instead of cached config, so they're config:cache-safe.
- `short-url.tenancy.plan_resolver` is no longer read. If you were using it, bind `Contracts\PlanResolver` instead (#5).

#### Upgrading

```php
// App\Providers\AppServiceProvider

use JeffersonGoncalves\LaravelShortUrl\Contracts\TenantResolver;
use JeffersonGoncalves\LaravelShortUrl\Contracts\PlanResolver;

public function register(): void
{
    // Resolve the current tenant without stancl/tenancy
    $this->app->bind(TenantResolver::class, function () {
        return new class implements TenantResolver {
            public function resolve(): int|string|null
            {
                return \App\Models\Tenant::current()?->id;
            }
        };
    });

    // Only needed if you used to set tenancy.plan_resolver
    $this->app->bind(PlanResolver::class, function () {
        return new class implements PlanResolver {
            public function resolve(int|string $tenantId): string
            {
                return \App\Models\Tenant::find($tenantId)?->plan ?? 'default';
            }
        };
    });
}















```
See the README's "Multi-tenancy without stancl/tenancy" section for the full walkthrough.

Fixes #5

## [v3.1.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v3.0.0...v3.1.0) - 2026-08-23

### Fixed

- `HeadersGeoIpDriver` (the default GeoIP driver) resolved geo from the live HTTP request, but `TrackShortUrlVisitJob` runs in a queue worker where that request no longer exists — every queued visit was stored with empty geo. Only `QUEUE_CONNECTION=sync` happened to work. The CDN headers are now snapshotted during the request (alongside the other request-scoped tracking fields) and carried through the job payload (#3).
- `tracking.trust_cdn_headers` is now actually wired up. Previously the flag existed in config and in the driver's own docblock but nothing read it — CDN geo headers were trusted unconditionally, even with the flag off.

#### Upgrading

If you use the default `headers` GeoIP driver, set:

```env
SHORT_URL_TRUST_CDN_HEADERS=true
















```
to keep getting geo data (only do this if your app is only reachable through the trusted edge/CDN injecting those headers).

Fixes #3

## [v3.0.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v2.0.0...v3.0.0) - 2026-08-23

### Breaking Changes

- `route.fallback` now defaults to `true`. Previously the package registered an explicit `GET /{urlKey}` route at the application root during boot — **before** the host app's own routes loaded — so any single-segment app route (`/about`, `/projects`, ...) was silently shadowed and 404'd (#2).
- The redirect route is now loaded from an `app()->booted()` callback, so it always registers after every other provider's routes, even if `fallback` is turned off manually.

#### Upgrading

If you rely on the old explicit-route behavior (e.g. you know for certain no app route can collide), set:

```env
SHORT_URL_ROUTE_FALLBACK=false

















```
Otherwise no action needed — the new default just prevents the collision.

Fixes #2

## [v2.0.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.3.0...v2.0.0) - 2026-08-19

### Breaking changes

Removes the following features entirely:

- **QR codes** — `QrCodeController`, `QrCodeBuilder` contract, Endroid driver, `qr_scans`/`qr_design` columns, `?source=qr` scan tracking, `GET /{urlKey}/qr` route.
- **API keys & REST API** — `ApiKey` model, `ApiKeyAuth` middleware, and the entire `/api/short-url/v1` REST API (links, stats, domains, visits, conversions, webhooks, export).
- **Bio pages / Bio links** — `BioPage`/`BioLink` models, `BioPageController`, `/bio/{handle}` routes, link-in-bio config.
- **Webhooks** — `Webhook`/`WebhookDelivery` models, `WebhookDispatcher` contract, `EloquentWebhookDispatcher`, `SendWebhookJob`, `PruneWebhookDeliveriesCommand`, `webhook_url`/`webhook_secret` columns, and the alert notification Slack/Discord/Teams webhook channels.
- **Deep links** — `DeepLinkRegistry`, `AppDefinition`, AASA/assetlinks controllers and routes, `auto_open_app_mobile`/`app_scheme_override` columns.

Consumers relying on any of the above must stay on the 1.x branch. Everything else (redirect pipeline, analytics, targeting, custom domains, conversions, multi-tenancy, pixels, alerts) is unchanged.

Migration stubs, config keys (`api`, `webhooks`, `qr`, `deep_links`, `bio`), and console commands tied to the removed features are gone — republish `short-url-config`/`short-url-migrations` after upgrading.

## [v1.3.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.2.1...v1.3.0) - 2026-08-19

Factories for every model, not just the 9 that already had one.

ApiKey::factory() threw class-not-found for a real consumer (a Filament plugin seeder) — it never used HasFactory at all. Auditing turned up 5 more models in the same state.

- Added factories for ApiKey, AuditLog, WebhookDelivery, Alert, Conversion, and Visit.
- Fixed two pre-existing factories that were broken standalone: BioLinkFactory never set bio_page_id (NOT NULL FK), AlertFactory never set triggered_at (NOT NULL) — both only worked because every caller in this repo's own tests passed an override.
- Added a regression test that creates one of every factory-backed model with no attribute overrides, so the next broken or missing factory fails CI instead of surfacing downstream.

## [v1.2.1](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.2.0...v1.2.1) - 2026-08-19

Fix: Model::factory() threw class-not-found outside this package's own tests.

Every HasFactory model relied solely on Factory::guessFactoryNamesUsing() registered in tests/TestCase.php — a test-only hook, invisible to any real consumer (a Filament plugin, a seeder, tinker). Laravel's default resolver assumes the App\Models convention and never matches a package namespace, so ShortUrl::factory() (and the other 8 factory-backed models) failed for anyone outside this repo's own test suite.

Fixed with newFactory() on each of the 9 HasFactory models — the standard, conflict-free mechanism (a global override would risk clobbering a host app's own factory resolution).

## [v1.2.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.1.0...v1.2.0) - 2026-08-18

Cross-link stats aggregation and a global stats endpoint.

- VisitRepository::aggregateMany(array $shortUrlIds, ...) — both Eloquent and ClickHouse drivers, same shape as aggregate() but summed across a set of links.
- StatsAggregator::forShortUrls(array $shortUrlIds) alongside the existing for($shortUrl) — link selection stays the caller's job via ShortUrl's own tenant-scoped query, the aggregator only does the math.
- GET /api/short-url/v1/stats (optionally ?folder_id=/?tag_id=) — a global breakdown across every link a caller can see, so a dashboard never has to query short_url_visits/short_url_daily_stats directly.
- Docs: README and the Laravel Boost skill/guideline updated to match.

## [v1.1.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.0.1...v1.1.0) - 2026-08-18

Enforceable UTM campaign tagging and a ready-to-use short_url in the API.

- ShortUrl.utm_source/medium/campaign/term/content are now live: attached to the destination on redirect (BuildFinalUrl, honoring strip_utm_from_destination) and used as the default attribution on a visit whenever the click itself carries no utm_* (DispatchTracking) — a link generated for one channel stays correctly attributed even if whoever shares it doesn't append query params.
- UtmTemplate is now usable as a reusable campaign preset: ShortUrlBuilder::utmTemplate()/utm()/customDomain(); ShortUrlManager applies a template's non-null fields as defaults under explicit attributes.
- short-url.utm.required (e.g. ['utm_medium']) makes ShortUrlManager reject creating or updating a link that doesn't declare those fields — enforced once in the manager, so it holds across the facade, builder, REST API, and every importer.
- ShortUrl::fullUrl() builds the ready-to-share link. ShortUrlResource now returns it as short_url, plus custom_domain_id and utm_*; LinkController's create/update/bulk validation now accepts all of them.
- Fix: ShortUrlManager::resolve($key, $host) now actually uses $host to scope resolution to a custom domain.
- Docs: README and the Laravel Boost skill/guideline updated to match.

## [v1.0.1](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.0.0...v1.0.1) - 2026-08-18

Docs and CI only — no functional changes.

- Add AGENTS.md and a Laravel Boost skill/guideline (resources/boost/) for AI coding agents.
- Expand README with destination-type (split/rules) examples and a conversion tracking example.
- Trim the Tests workflow to one PHP/Laravel combo across SQLite/MySQL/PostgreSQL, add concurrency cancellation, and cache Composer dependencies.
- Reset CHANGELOG.md and restore the v1.0.0 entry correctly.

## [v1.0.0](https://github.com/jeffersongoncalves/laravel-short-url/releases/tag/v1.0.0) - 2026-08-18

Initial stable release.

Headless URL-shortening engine for Laravel: redirect pipeline, custom domains, targeting rules, analytics (GA4, Plausible, PostHog, Matomo, Umami, Mixpanel, Segment), conversion tracking (Meta, Google, TikTok, LinkedIn), webhooks, QR codes, deep links, multi-tenancy, link-in-bio, and a REST API — all behind contracts, zero dependency on Filament.

See README for the full feature list and configuration reference.
