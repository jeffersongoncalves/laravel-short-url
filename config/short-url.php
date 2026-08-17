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

    // Additional keys for future phases (analytics, targeting, QR, security,
    // multi-tenancy, API, webhooks, ...) will be added here in later phases.

];
