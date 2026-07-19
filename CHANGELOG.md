# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.0] - 2026-07-19

### Added
- Opt-in exceptions: allow Shopware store communication, allow app-server communication, and an allow-list for specific app-server origins.

### Changed
- Prepared for public release: MIT license, PHP 8.2–8.5 support, GitHub metadata.
- Removed the redundant `guzzlehttp/guzzle` requirement (Guzzle is always provided by Shopware core).
- Documented why `BlockingGuzzleClient` intentionally extends `GuzzleHttp\Client` (concrete type required by Shopware consumers; the "considered final" notice is Dev-mode only).

## [1.0.0]

### Added
- Fail-closed blocking of Shopware telemetry, usage data / entity sync (GMV, orders), product analytics, service registry and store/tracking clients.
- Blocks cross-origin requests from the Administration (fetch / XHR / sendBeacon).
- Restores shop state on deactivate/uninstall (`core.store.apiUri`, scheduled tasks, usage-data run date).
