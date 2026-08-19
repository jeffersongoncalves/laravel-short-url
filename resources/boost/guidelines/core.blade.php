## Laravel Short URL Package

The `jeffersongoncalves/laravel-short-url` package is a headless URL-shortening engine: redirect pipeline, analytics, targeting rules, custom domains, conversion tracking, multi-tenancy. No Filament dependency — everything is reached through the `ShortUrl` facade, its contracts, or console commands.

### Package Namespace

All classes are under `JeffersonGoncalves\LaravelShortUrl`.

### Architecture

- **Facade**: `JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl` — `create()`, `destination()`, `resolve()`
- **Manager/Builder**: `ShortUrlManager` (facade target), `ShortUrlBuilder` (fluent creation)
- **Redirect pipeline**: `RedirectPipeline` running stages in `src/Pipeline/Stages/` — cache-backed, each stage can short-circuit with a `Response`
- **Contracts**: `src/Contracts/` — `VisitRepository`, `GeoIpDriver`, `VpnDetectionDriver`, `AnalyticsDriver`, `SafeBrowsingChecker`, `StatsAggregator`, `TargetingResolver`, `DnsVerifier`, `SettingsRepository`, `ImporterDriver`, `ConversionApiDispatcher`
- **Registries** (use `extend()`, not rebind): `AnalyticsDriverRegistry`, `PixelProviderRegistry`, `FilterTypeRegistry`, `ImporterDriverRegistry`

### Key Conventions

- All database tables use the `short_url_` prefix (`table_prefix` config)
- `custom_domain_id` is `NOT NULL`, sentinel `0` means "no custom domain" — never `null` (NULL != NULL breaks the composite unique index); the builder/manager coerce `null` to `0` for you
- Redirects happen via `GET /{urlKey}` already routed by the package — never write a competing redirect controller
- Visit tracking is asynchronous (`TrackShortUrlVisitJob`) and never blocks/breaks a redirect
- Multi-tenancy is entirely feature-flagged (`short-url.tenancy.enabled`), a complete no-op when off
- Configuration lives in `config/short-url.php`, fully commented inline

### Creating Links

@verbatim
<code-snippet name="Fluent creation via ShortUrlBuilder" lang="php">
use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;

$link = ShortUrl::destination('https://example.com/product')
    ->key('promo25')
    ->expiresAt(now()->addDays(30))
    ->maxVisits(1000)
    ->password('secret')
    ->create();
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Campaign tagging via UTM template" lang="php">
use JeffersonGoncalves\LaravelShortUrl\Models\UtmTemplate;

$campaign = UtmTemplate::create(['name' => 'Spring SMS', 'utm_medium' => 'sms']);

$link = ShortUrl::destination('https://example.com/product')
    ->utmTemplate($campaign->id)        // fills in unset utm_* fields
    ->utm(['utm_source' => 'agent-42']) // explicit values always win
    ->create();
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Split (A/B) and rules destinations" lang="php">
ShortUrl::create([
    'destination_url' => 'https://example.com/base',
    'destination_type' => 'split',
    'rotation_variants' => [
        ['url' => 'https://a.test', 'weight' => 50, 'label' => 'A'],
        ['url' => 'https://b.test', 'weight' => 50, 'label' => 'B'],
    ],
]);

ShortUrl::create([
    'destination_url' => 'https://example.com/base', // fallback
    'destination_type' => 'rules',
    'targeting_rules' => [
        ['conditions' => [['type' => 'country', 'value' => 'FR']], 'destination' => 'https://example.com/france'],
    ],
]);
</code-snippet>
@endverbatim

### The Redirect Pipeline

`ResolveHost → RateLimit → ResolveShortUrl(cache) → DetectBot → DetectVpnProxy → CheckAvailability → RequirePassword → ShowWarning → ResolveDestination → BuildFinalUrl → RenderInterstitial → Respond → DispatchTracking`

Each stage short-circuits by returning a `Response` (wrong password, expired link, blocked VPN, plan limit). The resolved link is cached (`{host}:{key}`), invalidated on `saved`/`deleted`.

### Extending Analytics / Conversions

@verbatim
<code-snippet name="Registering a custom analytics driver" lang="php">
use JeffersonGoncalves\LaravelShortUrl\Registries\AnalyticsDriverRegistry;

$this->app->make(AnalyticsDriverRegistry::class)
    ->extend('my_provider', fn () => new MyAnalyticsDriver);
</code-snippet>
@endverbatim

Built-in analytics drivers: GA4, Plausible, PostHog, Matomo, Umami, Mixpanel, Segment — each gated by `short-url.analytics.{name}.enabled`. Built-in conversion API dispatchers: Meta, Google Enhanced Conversions, TikTok, LinkedIn — selected via `short-url.conversions.driver`.

### Cross-Link Stats

Never compute your own aggregation over `short_url_visits`/`short_url_daily_stats` for a multi-link (dashboard) breakdown — `Contracts\StatsAggregator::forShortUrls(array $shortUrlIds)` does it (same contract as `for($shortUrl)`, just summed across the set). Resolving which links belong in the set — a folder, a tag, everything a tenant owns — is the caller's job via `ShortUrl`'s own tenant-scoped Eloquent query; the aggregator only does the math.

### Required UTM Enforcement

`short-url.utm.required` (e.g. `['utm_medium']`) makes `ShortUrlManager` reject creating/updating a link that doesn't declare those fields — enforced once in the manager, so it's uniform across the facade, builder, and every importer.

### Events

`ShortUrlVisited` (`$shortUrl`, `$visit`), `ConversionRecorded` (`$conversion`), `AlertTriggered` (Z-score anomaly detection against a 7-day baseline).

### Testing Against This Package

On Postgres, wrap assertions expecting a `QueryException` in `DB::transaction()` — an uncaught error aborts the whole enclosing transaction until a rollback, which would otherwise poison `RefreshDatabase`'s per-test transaction for whatever query runs next.
