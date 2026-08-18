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

    // Additional keys for future phases (QR, multi-tenancy, API,
    // webhooks, ...) will be added here in later phases.

];
