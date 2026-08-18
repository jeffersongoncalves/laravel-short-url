# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/jeffersongoncalves/laravel-short-url/compare/v1.0.0...HEAD)

### Added

- Core short URL model, migrations and config (F1).
- Base62 `KeyGenerator` with collision retry and blacklist support.
- Cache-backed redirect pipeline (`ResolveHost`, `ResolveShortUrl`, `CheckAvailability`, `BuildFinalUrl`, `Respond`) with stub stages reserved for future phases (rate limiting, bot/VPN detection, password protection, warning page, interstitial, tracking).
- Catch-all redirect route with configurable prefix/domain/middleware.

## [v1.0.0](https://github.com/jeffersongoncalves/laravel-short-url/compare/1.0.0...v1.0.0) - 2026-08-18

Initial stable release.

Headless URL-shortening engine for Laravel: redirect pipeline, custom domains, targeting rules, analytics (GA4, Plausible, PostHog, Matomo, Umami, Mixpanel, Segment), conversion tracking (Meta, Google, TikTok, LinkedIn), webhooks, QR codes, deep links, multi-tenancy, link-in-bio, and a REST API — all behind contracts, zero dependency on Filament.

See README for the full feature list and configuration reference.
