# 002. Use a custom PSR-16 file cache instead of a cache library

Date: 2026-11-21

## Status

Accepted

## Context

`GET /weather` calls the Open-Meteo API on every request. To protect against
the API being unavailable and to shave latency, responses are cached on disk
with a 5-minute TTL.

Candidate solutions evaluated:

- **Symfony Cache** (`symfony/cache` + `FilesystemAdapter`): actively
  maintained, PSR-6 + PSR-16, but pulls a handful of packages
  (`symfony/cache`, `symfony/cache-contracts`, `symfony/var-exporter`,
  `psr/log`) and wiring guidance for a use case with exactly one key.
- **PHP Cache** (`cache/filesystem-adapter`): pure PSR-6/16 focused, but
  development activity has been low for years.
- **Stash** (`tedivm/stash`): mature, multi-driver; more surface than needed
  and a smaller community.
- **Custom implementation**: a small PSR-16 file cache (interface provided by
  `psr/simple-cache ^3.0`) with unit tests, fully under our control.

The cache unit is deliberately trivial for now: one key
(`volos-current-weather`), one payload type (`Weather`), one directory.

## Decision

We implement a custom backend-agnostic file cache, `App\Cache\FileCache`,
implementing `Psr\SimpleCache\CacheInterface` (`psr/simple-cache ^3.0`), and
use it through `App\Weather\CachedOpenMeteoClient`, a decorator around the
Open-Meteo client. Entries are stored as hashed files containing a serialized
`{expires_at, value}` payload; the TTL supports int seconds and
`DateInterval` and defaults to 300 seconds.

## Consequences

- No new transitive dependencies beyond the tiny, framework-agnostic
  `psr/simple-cache` contract; the app logic does not depend on any concrete
  cache library.
- The API boundary remains standard PSR-16, so swapping to Symfony Cache,
  APCu, or Redis later is a one-line change in `public/index.php`.
- All behavior is explicit and covered by unit tests (expiry, corrupted
  entries, invalid keys); nothing to debug inside a third-party adapter.
- We own the trade-offs ourselves: `serialize()` round-trips values, storage
  is per-machine (no shared cache across servers), and expired entries are
  cleaned up lazily — deleted on first access after expiry, then refreshed by
  the next fetch through `CachedOpenMeteoClient`. These limitations are
  acceptable for a single-host, single-key cache; revisit this ADR if the app
  is deployed multi-host or the key space grows.
