# AGENTS.md

Instructions for AI coding agents working in this repository.

## Project

`jeffersongoncalves/laravel-short-url` — a headless URL-shortening engine for Laravel (redirect pipeline, analytics, targeting rules, custom domains, webhooks, conversion tracking, QR codes, multi-tenancy, link-in-bio). No Filament dependency; consumed via its `ShortUrl` facade, contracts, REST API, or console commands. See [README.md](README.md) for the full feature list and [resources/boost/skills/short-url-development/SKILL.md](resources/boost/skills/short-url-development/SKILL.md) for a deeper development reference.

## Setup

```bash
composer install
```

## Commands

```bash
composer test           # Pest test suite
composer test-coverage  # Pest with coverage
composer analyse        # PHPStan (Larastan) level 6
composer format          # Pint (Laravel preset)
```

Run all three (`test`, `analyse`, `format`) before considering a change done — CI enforces all three on every push.

## Conventions

- **Namespace**: everything under `JeffersonGoncalves\LaravelShortUrl\`; tests under `JeffersonGoncalves\LaravelShortUrl\Tests\`.
- **Contracts first**: swappable behavior (analytics driver, DNS verifier, QR builder, VPN detector, conversion dispatcher, ...) is an interface under `src/Contracts/`, bound in `LaravelShortUrlServiceProvider`. Add a new provider by implementing the contract and registering it there — don't special-case call sites.
- **`custom_domain_id` is `NOT NULL`**, sentinel `0` means "no custom domain" — never pass `null` directly into a raw query; the manager/builder coerce it. This exists because `NULL != NULL` in a composite unique index would silently break `unique(custom_domain_id, url_key)`.
- **Never bypass the redirect pipeline.** `GET /{urlKey}` already routes through caching, security checks, and tracking — don't write a competing redirect controller.
- **Tracking is async and must stay non-blocking.** A failure in GeoIP, Safe Browsing, VPN detection, an analytics driver, or a webhook delivery must never break a redirect or a test relying on one.
- **Multi-tenancy is a pure feature flag** (`short-url.tenancy.enabled`) — a complete no-op when off. Don't add tenancy-aware code paths that behave differently when the flag is off; gate on the flag explicitly.
- **PHP 8.3+, Laravel 12 or 13.** Don't add code that only works on one of the two supported majors without a compatibility check.

## Testing notes

- Pest 4 on Orchestra Testbench, SQLite in-memory by default. `tests/TestCase.php` reads `SHORT_URL_TEST_DB_*` env vars (not `DB_*`, which Testbench itself sets by convention) to point at MySQL/Postgres in CI.
- Migration order matters: `tests/TestCase.php::defineDatabaseMigrations()` copies stubs using the order in `LaravelShortUrlServiceProvider::MIGRATIONS`, not alphabetically — a foreign-key-dependent table (e.g. bio_links → bio_pages) must stay listed after what it depends on in that array.
- **On Postgres**, wrap any assertion expecting a `QueryException` (e.g. a uniqueness constraint) in `DB::transaction()`. Postgres aborts the whole enclosing transaction on any uncaught error until a rollback — that poisons `RefreshDatabase`'s per-test transaction for whatever query runs next, unlike MySQL/SQLite.
- Never edit `CHANGELOG.md` by hand except to fix a broken auto-generated entry — `.github/workflows/update-changelog.yml` populates it from GitHub Releases, and it only fires once per release.

## Commit style

Conventional commits (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`, `ci:`), English, explaining *why* over *what*. Primary branch is `master`.
