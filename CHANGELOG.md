# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Core short URL model, migrations and config (F1).
- Base62 `KeyGenerator` with collision retry and blacklist support.
- Cache-backed redirect pipeline (`ResolveHost`, `ResolveShortUrl`, `CheckAvailability`, `BuildFinalUrl`, `Respond`) with stub stages reserved for future phases (rate limiting, bot/VPN detection, password protection, warning page, interstitial, tracking).
- Catch-all redirect route with configurable prefix/domain/middleware.

[Unreleased]: https://github.com/jeffersongoncalves/laravel-short-url/compare/1.0.0...HEAD
