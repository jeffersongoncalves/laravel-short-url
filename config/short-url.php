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

    // Additional keys for future phases (targeting, QR, security,
    // multi-tenancy, API, webhooks, ...) will be added here in later phases.

];
