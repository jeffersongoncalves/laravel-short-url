# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
