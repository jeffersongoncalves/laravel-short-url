# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
