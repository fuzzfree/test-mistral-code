# test-mistral-code

- Simple Slim PHP app to fetch weather data for Volos (Greece) from https://open-meteo.com/en/docs
- Use latest Slim version
- Use latest PHP version with Strict Types
- Use latest PHPStan version with Level 9
- Use latest PHPUnit
- Use latest PHP Coding Standards Fixer with the [PSR12](https://cs.symfony.com/doc/ruleSets/PSR12.html) rule set

## Getting started

Requirements: PHP 8.5+ and Composer.

```bash
composer install

# run static analysis (level 9)
composer stan

# check code style ([PSR12](https://cs.symfony.com/doc/ruleSets/PSR12.html))
composer cs-check

# fix code style ([PSR12](https://cs.symfony.com/doc/ruleSets/PSR12.html))
composer cs-fix

# run the test suite
composer test

# start the app
php -S localhost:8080 public/index.php
```

## Endpoints

| Route          | Description                                            |
|----------------|--------------------------------------------------------|
| `GET /`        | Service info                                           |
| `GET /weather` | Current weather for Volos, Greece (Open-Meteo API)     |

Example:

```bash
php -S localhost:8080 public/index.php
curl http://localhost:8080/weather
```

`GET /` returns:

```json
{"ok":true,"endpoints":["\/weather"]}
```

`GET /weather` returns a JSON payload built from the Open-Meteo
[forecast API](https://open-meteo.com/en/docs), including temperature,
apparent temperature, humidity, precipitation, WMO weather code with a human
description, day/night flag, and wind speed/direction, using the
`Europe/Athens` timezone:

```json
{
    "latitude": 39.364506,
    "longitude": 22.960554,
    "timezone": "Europe/Athens",
    "time": "2026-11-21T23:00",
    "current": {
        "temperature_c": 15.6,
        "apparent_temperature_c": 13.3,
        "relative_humidity_percent": 56,
        "precipitation_mm": 0,
        "weather_code": 2,
        "description": "Partly cloudy",
        "is_day": false,
        "wind_speed_kmh": 10.3,
        "wind_direction_deg": 348
    }
}
```

## Caching

`GET /weather` responses are cached on disk for **5 minutes** (300 seconds)
to avoid hitting the Open-Meteo API on every request:

- `src/Cache/FileCache.php` — small PSR-16 (`psr/simple-cache`) file cache:
  each entry is a serialized payload with its expiry stored as a hashed file
  in the cache directory (default: `sys_get_temp_dir()/test-mistral-code-cache`)
- `src/Weather/CachedOpenMeteoClient.php` — serves `Weather` from the cache
  when fresh, otherwise fetches from Open-Meteo and stores the result

## Layout

- `public/index.php` — Slim application and routes
- `src/Http/` — fetcher abstraction with a `get(string $url): string` client
- `src/Weather/` — Open-Meteo client, cached client decorator, weather DTO, WMO code descriptions
- `src/Cache/` — PSR-16 file cache with per-entry TTL
- `tests/` — PHPUnit tests (client hydration, DTO shape, code mapping)
- `.php-cs-fixer.dist.php` — [PHP-CS-Fixer](https://cs.symfony.com/) config with the `@PSR12` rule set
