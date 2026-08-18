<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix applied to all tables created by this package to avoid
    | collisions with existing application tables.
    |
    */
    'table_prefix' => env('SHORT_URL_TABLE_PREFIX', 'short_url_'),

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    |
    | Configuration for the redirect route (GET /{urlKey}).
    | - prefix: URL prefix for the redirect route. Empty string means the
    |   route is registered at the application root.
    | - domain: Restrict the redirect route to a specific domain/subdomain.
    |   Leave null to accept requests on any domain the app responds to.
    | - middleware: Middleware group(s) applied to the redirect route.
    |
    */
    'route' => [
        'prefix' => env('SHORT_URL_ROUTE_PREFIX', ''),
        'domain' => env('SHORT_URL_ROUTE_DOMAIN'),
        'middleware' => ['web'],

        // When true, the redirect route is registered as the application's
        // fallback route instead of an explicit `/{urlKey}` route, so host
        // app routes always take precedence over short URL keys.
        'fallback' => env('SHORT_URL_ROUTE_FALLBACK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Generation
    |--------------------------------------------------------------------------
    |
    | Settings for the auto-generated Base62 short URL key.
    | - length: number of characters in generated keys.
    | - blacklist: reserved words that can never be used/generated as a key.
    |
    */
    'key' => [
        'length' => env('SHORT_URL_KEY_LENGTH', 7),
        'blacklist' => [
            'admin', 'api', 'login', 'logout', 'register', 'dashboard',
            'settings', 'app', 'www', 'short-url',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    |
    | Default HTTP status code used for redirects when a short URL does not
    | specify its own `redirect_status_code`.
    |
    */
    'redirect' => [
        'default_status_code' => env('SHORT_URL_DEFAULT_STATUS_CODE', 302),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Resolved short URLs are cached to avoid a database hit on every
    | redirect. Cache entries are automatically invalidated when a short
    | URL is saved or deleted (see ShortUrlObserver).
    |
    */
    'cache' => [
        'enabled' => env('SHORT_URL_CACHE_ENABLED', true),
        'ttl' => env('SHORT_URL_CACHE_TTL', 3600),
        'prefix' => env('SHORT_URL_CACHE_PREFIX', 'short_url'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking & Analytics
    |--------------------------------------------------------------------------
    |
    | - driver: VisitRepository implementation. "eloquent" ships with the
    |   package; "clickhouse" is added in a later phase.
    | - trust_cdn_headers: read geo data from CDN-injected headers (see
    |   HeadersGeoIpDriver) instead of/before calling an external service.
    | - geoip.driver: headers|ip_api|maxmind.
    | - counter_buffering: buffer visit counters in Redis instead of writing
    |   the short_urls row on every redirect. Flushed by
    |   short-url:sync-counters. Falls back to a queued direct DB increment
    |   (IncrementVisitJob) when disabled or when Redis is unreachable.
    | - ip_hash_salt: salt mixed into the stored IP hash. Rotate periodically;
    |   rotating it breaks unique-visit continuity by design (LGPD).
    | - retention_days: visit rows older than this are pruned by
    |   short-url:aggregate-and-prune after being folded into daily_stats.
    |
    */
    'tracking' => [
        'driver' => env('SHORT_URL_VISIT_REPOSITORY', 'eloquent'),

        'trust_cdn_headers' => env('SHORT_URL_TRUST_CDN_HEADERS', false),

        'geoip' => [
            'driver' => env('SHORT_URL_GEOIP_DRIVER', 'headers'),
            'maxmind_database_path' => env('SHORT_URL_MAXMIND_DB_PATH'),
        ],

        'counter_buffering' => env('SHORT_URL_COUNTER_BUFFERING', false),
        'redis_connection' => env('SHORT_URL_REDIS_CONNECTION', 'default'),

        'ip_hash_salt' => env('SHORT_URL_IP_HASH_SALT'),

        'retention_days' => env('SHORT_URL_VISIT_RETENTION_DAYS', 400),

        // Only read when driver is "clickhouse". Talks to ClickHouse's
        // native HTTP interface directly — no client library required.
        'clickhouse' => [
            'host' => env('SHORT_URL_CLICKHOUSE_HOST'),
            'port' => env('SHORT_URL_CLICKHOUSE_PORT', 8123),
            'database' => env('SHORT_URL_CLICKHOUSE_DATABASE', 'default'),
            'username' => env('SHORT_URL_CLICKHOUSE_USERNAME', 'default'),
            'password' => env('SHORT_URL_CLICKHOUSE_PASSWORD', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Domains
    |--------------------------------------------------------------------------
    |
    | - enabled: resolve short urls against verified custom domains in
    |   addition to the app's own host. Off by default: a plain install
    |   never pays for the extra CustomDomain lookup on every redirect.
    | - max_verification_failures: consecutive failed DNS checks (via
    |   short-url:verify-domains) before a domain is auto-disabled.
    |
    */
    'domains' => [
        'enabled' => env('SHORT_URL_DOMAINS_ENABLED', false),
        'max_verification_failures' => env('SHORT_URL_DOMAIN_MAX_FAILURES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Shown on the package's own interstitial pages (password prompt,
    | warning, expired link).
    |
    */
    'branding' => [
        'site_name' => env('SHORT_URL_SITE_NAME', env('APP_NAME', 'Laravel')),
        'logo_url' => env('SHORT_URL_LOGO_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | - password.unlock_ttl_minutes: how long a correct password unlocks a
    |   protected link for, per browser session.
    | - warning.token_ttl_minutes: how long the warning interstitial's
    |   signed "continue" link stays valid.
    | - rate_limit: per-IP throttling on the redirect route itself. Off by
    |   default — most installs sit behind an edge/CDN limiter already.
    | - vpn_detection.mode: off|flag|block. "flag" only records is_vpn/
    |   is_proxy/... on the visit; "block" also 403s the redirect.
    | - safe_browsing: scans a short url's destination (and, for split/
    |   rules types, every variant/rule destination) via Google Safe
    |   Browsing. mode "sync" blocks create/update on an unsafe verdict;
    |   "async" saves immediately and updates safe_browsing_status once the
    |   check completes.
    |
    */
    'security' => [
        'password' => [
            'unlock_ttl_minutes' => env('SHORT_URL_PASSWORD_UNLOCK_TTL', 60),
        ],

        'warning' => [
            'token_ttl_minutes' => env('SHORT_URL_WARNING_TOKEN_TTL', 30),
        ],

        'rate_limit' => [
            'enabled' => env('SHORT_URL_RATE_LIMIT_ENABLED', false),
            'max_attempts' => env('SHORT_URL_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_seconds' => env('SHORT_URL_RATE_LIMIT_DECAY_SECONDS', 60),
        ],

        'vpn_detection' => [
            'mode' => env('SHORT_URL_VPN_DETECTION_MODE', 'off'),
            'driver' => env('SHORT_URL_VPN_DETECTION_DRIVER', 'ip_api'),
            'cache_ttl' => env('SHORT_URL_VPN_DETECTION_CACHE_TTL', 3600),
            'proxycheck_api_key' => env('SHORT_URL_PROXYCHECK_API_KEY'),
        ],

        'safe_browsing' => [
            'enabled' => env('SHORT_URL_SAFE_BROWSING_ENABLED', false),
            'mode' => env('SHORT_URL_SAFE_BROWSING_MODE', 'sync'),
            'api_key' => env('SHORT_URL_SAFE_BROWSING_API_KEY'),
            'bypass' => env('SHORT_URL_SAFE_BROWSING_BYPASS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance (LGPD/GDPR)
    |--------------------------------------------------------------------------
    |
    | - analytics_only: when true, visits are aggregated into daily_stats
    |   as usual but no personally-identifiable fields (ip_hash,
    |   ip_anonymized, user_agent_hash) are stored on the raw visit row.
    |
    */
    'compliance' => [
        'analytics_only' => env('SHORT_URL_ANALYTICS_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('SHORT_URL_AUDIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | REST API
    |--------------------------------------------------------------------------
    |
    | Off by default — the API surfaces every link's destination and stats
    | over HTTP, so it's an explicit opt-in. Authenticated via a Bearer
    | short_url_api_keys token (see ApiKeyAuth), not the host app's own
    | auth guard.
    |
    */
    'api' => [
        'enabled' => env('SHORT_URL_API_ENABLED', false),
        'prefix' => env('SHORT_URL_API_PREFIX', 'api/short-url/v1'),

        'rate_limit' => [
            'max_attempts' => env('SHORT_URL_API_RATE_LIMIT_MAX_ATTEMPTS', 300),
            'decay_seconds' => env('SHORT_URL_API_RATE_LIMIT_DECAY_SECONDS', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | - retry_seconds: delay before each retry attempt, in order.
    | - max_failures: consecutive failures (across all attempts of all
    |   deliveries) before a webhook is auto-disabled.
    |
    */
    'webhooks' => [
        'retry_seconds' => [10, 60, 300],
        'max_failures' => env('SHORT_URL_WEBHOOK_MAX_FAILURES', 20),
        'delivery_retention_days' => env('SHORT_URL_WEBHOOK_DELIVERY_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Drivers
    |--------------------------------------------------------------------------
    |
    | Server-side analytics forwarding on every tracked visit, in addition
    | to this package's own stats. Each entry is enabled independently.
    |
    */
    'analytics' => [
        'ga4' => [
            'enabled' => env('SHORT_URL_ANALYTICS_GA4_ENABLED', false),
            'measurement_id' => env('SHORT_URL_GA4_MEASUREMENT_ID'),
            'api_secret' => env('SHORT_URL_GA4_API_SECRET'),
        ],
        'plausible' => [
            'enabled' => env('SHORT_URL_ANALYTICS_PLAUSIBLE_ENABLED', false),
            'api_host' => env('SHORT_URL_PLAUSIBLE_API_HOST', 'https://plausible.io'),
            'domain' => env('SHORT_URL_PLAUSIBLE_DOMAIN'),
        ],
        'posthog' => [
            'enabled' => env('SHORT_URL_ANALYTICS_POSTHOG_ENABLED', false),
            'host' => env('SHORT_URL_POSTHOG_HOST', 'https://us.i.posthog.com'),
            'api_key' => env('SHORT_URL_POSTHOG_API_KEY'),
        ],
        'matomo' => [
            'enabled' => env('SHORT_URL_ANALYTICS_MATOMO_ENABLED', false),
            'url' => env('SHORT_URL_MATOMO_URL'),
            'site_id' => env('SHORT_URL_MATOMO_SITE_ID'),
            'token_auth' => env('SHORT_URL_MATOMO_TOKEN_AUTH'),
        ],
        'umami' => [
            'enabled' => env('SHORT_URL_ANALYTICS_UMAMI_ENABLED', false),
            'host' => env('SHORT_URL_UMAMI_HOST'),
            'website_id' => env('SHORT_URL_UMAMI_WEBSITE_ID'),
        ],
        'mixpanel' => [
            'enabled' => env('SHORT_URL_ANALYTICS_MIXPANEL_ENABLED', false),
            'token' => env('SHORT_URL_MIXPANEL_TOKEN'),
        ],
        'segment' => [
            'enabled' => env('SHORT_URL_ANALYTICS_SEGMENT_ENABLED', false),
            'write_key' => env('SHORT_URL_SEGMENT_WRITE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversions
    |--------------------------------------------------------------------------
    |
    | Server-to-server forwarding of conversions recorded via POST
    | /conversions. "driver" selects which API a conversion is also sent
    | to; "none" just records it locally.
    |
    */
    'conversions' => [
        'driver' => env('SHORT_URL_CONVERSIONS_DRIVER', 'none'),

        'meta' => [
            'pixel_id' => env('SHORT_URL_META_PIXEL_ID'),
            'access_token' => env('SHORT_URL_META_ACCESS_TOKEN'),
        ],

        'google' => [
            'customer_id' => env('SHORT_URL_GOOGLE_ADS_CUSTOMER_ID'),
            'developer_token' => env('SHORT_URL_GOOGLE_ADS_DEVELOPER_TOKEN'),
            'access_token' => env('SHORT_URL_GOOGLE_ADS_ACCESS_TOKEN'),
            'conversion_action_id' => env('SHORT_URL_GOOGLE_ADS_CONVERSION_ACTION_ID'),
        ],

        'tiktok' => [
            'pixel_code' => env('SHORT_URL_TIKTOK_PIXEL_CODE'),
            'access_token' => env('SHORT_URL_TIKTOK_ACCESS_TOKEN'),
        ],

        'linkedin' => [
            'access_token' => env('SHORT_URL_LINKEDIN_ACCESS_TOKEN'),
            'conversion_id' => env('SHORT_URL_LINKEDIN_CONVERSION_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'anomaly_z_threshold' => env('SHORT_URL_ALERT_Z_THRESHOLD', 3.0),
        'baseline_days' => env('SHORT_URL_ALERT_BASELINE_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Operator-level recipients for alerts and scheduled reports — this
    | package has no user model of its own, so these are plain config
    | values rather than per-user preferences. Each channel activates the
    | moment its config is present; leave it null/empty to skip it.
    |
    */
    'notifications' => [
        'mail_to' => array_filter(explode(',', (string) env('SHORT_URL_NOTIFY_MAIL_TO', ''))),
        'database_enabled' => env('SHORT_URL_NOTIFY_DATABASE_ENABLED', false),
        'broadcast_enabled' => env('SHORT_URL_NOTIFY_BROADCAST_ENABLED', false),
        'slack_webhook_url' => env('SHORT_URL_NOTIFY_SLACK_WEBHOOK_URL'),
        'discord_webhook_url' => env('SHORT_URL_NOTIFY_DISCORD_WEBHOOK_URL'),
        'teams_webhook_url' => env('SHORT_URL_NOTIFY_TEAMS_WEBHOOK_URL'),
        'telegram_bot_token' => env('SHORT_URL_NOTIFY_TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('SHORT_URL_NOTIFY_TELEGRAM_CHAT_ID'),
        'scheduled_reports_enabled' => env('SHORT_URL_SCHEDULED_REPORTS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Codes
    |--------------------------------------------------------------------------
    |
    | Requires the optional endroid/qr-code package (see composer.json
    | "suggest"). driver is currently fixed to "endroid" — the config key
    | exists for a future alternate driver.
    |
    */
    'qr' => [
        'driver' => env('SHORT_URL_QR_DRIVER', 'endroid'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deep Links (mobile app opening)
    |--------------------------------------------------------------------------
    |
    | - aasa/assetlinks: serve the well-known files iOS/Android need to
    |   trust this domain for Universal/App Links. Off by default and
    |   require operator-supplied app identifiers — the package has no way
    |   to know the host app's Apple Team ID or Android signing fingerprint.
    |
    */
    'deep_links' => [
        'aasa' => [
            'enabled' => env('SHORT_URL_AASA_ENABLED', false),
            // e.g. ['TEAMID.com.example.app']
            'app_ids' => array_filter(explode(',', (string) env('SHORT_URL_AASA_APP_IDS', ''))),
            'paths' => ['*'],
        ],
        'assetlinks' => [
            'enabled' => env('SHORT_URL_ASSETLINKS_ENABLED', false),
            // Populate via config/short-url.php override: [['package' => 'com.example.app', 'fingerprints' => ['AA:BB:...']]]
            'apps' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pixels
    |--------------------------------------------------------------------------
    |
    | - require_consent: when true, the interstitial shows an accept/
    |   decline banner before firing any attached pixel script instead of
    |   firing them automatically.
    |
    */
    'pixels' => [
        'require_consent' => env('SHORT_URL_PIXELS_REQUIRE_CONSENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Importers
    |--------------------------------------------------------------------------
    */
    'importers' => [
        'bitly' => [
            'access_token' => env('SHORT_URL_BITLY_ACCESS_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Off by default — the package works standalone (single tenant) with
    | stancl/tenancy entirely absent. Turning this on:
    | - scopes every tenant_id-bearing model (ShortUrl, CustomDomain,
    |   ApiKey, Webhook, Folder, Tag, UtmTemplate, Pixel) and the settings
    |   table to the current tenant, resolved via stancl/tenancy's tenant()
    |   helper when installed, else current_tenant_id below.
    | - enforces the plan limits below on link/domain creation.
    |
    | - current_tenant_id: manual override when not using stancl/tenancy
    |   (e.g. host apps with their own tenancy, or tests).
    | - plan_resolver: Closure(int|string $tenantId): string returning
    |   which key of "plans" the tenant is on. Defaults to "default".
    | - plans.*.{links_per_month,domains}: null means unlimited.
    |
    */
    'tenancy' => [
        'enabled' => env('SHORT_URL_TENANCY_ENABLED', false),
        'current_tenant_id' => env('SHORT_URL_CURRENT_TENANT_ID'),
        'plan_resolver' => null,
        'plans' => [
            'default' => [
                'links_per_month' => null,
                'clicks_per_month' => null,
                'domains' => null,
                'members' => null,
                'retention_days' => null,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Link-in-Bio
    |--------------------------------------------------------------------------
    |
    | Off by default. Public pages render at /{prefix}/{handle} (default
    | "bio"), deliberately never at the app root, so a handle can never
    | collide with a short url key. Building/editing pages is the plugin's
    | job — this package only stores the data model and renders/tracks.
    |
    */
    'bio' => [
        'enabled' => env('SHORT_URL_BIO_ENABLED', false),
        'prefix' => env('SHORT_URL_BIO_PREFIX', 'bio'),
    ],

    // Additional keys for future phases will be added here.

];
