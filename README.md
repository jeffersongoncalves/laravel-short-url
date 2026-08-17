# Laravel Short URL

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-short-url/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-short-url/actions?query=workflow%3Atests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-short-url/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-short-url/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](LICENSE.md)

A framework-agnostic-within-Laravel package for creating and redirecting short URLs. It owns the model, migrations,
key generation and an extensible redirect pipeline. It has **no UI** of its own — pair it with
[`jeffersongoncalves/filament-short-url`](https://github.com/jeffersongoncalves/filament-short-url) for a Filament
admin panel, or build your own UI on top of it.

## Status: Phase F1

This is the foundational phase. It ships:

- The `short_urls` and `short_url_settings` tables and the `ShortUrl` model.
- A Base62 key generator with collision retry and a reserved-word blacklist.
- A cache-backed, extensible redirect pipeline (`GET /{urlKey}`) covering resolution, availability checks
  (enabled/expired/deactivated/max visits), query-param forwarding and the final redirect response.
- Stub pipeline stages (rate limiting, bot/VPN detection, password protection, warning page, interstitial,
  tracking dispatch) ready to be implemented in later phases without changing the pipeline's public contract.

Analytics, split/rule/geo-fence destination types, QR codes, multi-tenancy, a public API and webhooks are **not**
part of F1 and will land in future releases.

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/laravel-short-url
```

Publish the config file and migrations:

```bash
php artisan vendor:publish --tag="laravel-short-url-config"
php artisan vendor:publish --tag="laravel-short-url-migrations"
php artisan migrate
```

## Configuration

All keys accept an `.env` override. See `config/short-url.php` after publishing for full comments.

```env
SHORT_URL_TABLE_PREFIX=short_url_
SHORT_URL_ROUTE_PREFIX=
SHORT_URL_ROUTE_DOMAIN=
SHORT_URL_KEY_LENGTH=7
SHORT_URL_DEFAULT_STATUS_CODE=302
SHORT_URL_CACHE_ENABLED=true
SHORT_URL_CACHE_TTL=3600
SHORT_URL_CACHE_PREFIX=short_url
```

| Key | Default | Description |
|---|---|---|
| `table_prefix` | `short_url_` | Prefix for tables created by this package. |
| `route.prefix` | `''` | URL prefix for the redirect route. Empty means root-level (`/{urlKey}`). |
| `route.domain` | `null` | Restrict the redirect route to a specific domain. |
| `route.middleware` | `['web']` | Middleware applied to the redirect route. |
| `key.length` | `7` | Length of auto-generated Base62 keys. |
| `key.blacklist` | `[admin, api, ...]` | Reserved words that can never be used/generated as a key. |
| `redirect.default_status_code` | `302` | Default redirect status code for new short URLs. |
| `cache.enabled` | `true` | Whether resolved short URLs are cached. |
| `cache.ttl` | `3600` | Cache TTL in seconds. |
| `cache.prefix` | `short_url` | Cache key prefix. |

## Usage

### Creating a short URL

```php
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\Services\KeyGenerator;

$shortUrl = ShortUrl::make()
    ->to('https://example.com/some/very/long/path')
    ->key(app(KeyGenerator::class)->generate()) // omit to leave url_key null and set it yourself
    ->title('My campaign link')
    ->maxVisits(1000)
    ->expiresAt(now()->addMonth());

$shortUrl->save();
```

### Redirecting

The package registers a catch-all `GET /{urlKey}` route (name: `short-url.redirect`) that resolves the short URL,
checks availability (enabled, not expired, not deactivated, under `max_visits`), builds the final destination URL
(forwarding the query string when `forward_query_params` is enabled) and issues the redirect using the link's
`redirect_status_code`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
