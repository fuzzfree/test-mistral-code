# test-mistral-code

- Simple Slim php app to fetch weather data for Volos (Greece) from https://open-meteo.com/en/docs
- Use latest slim version
- Use latest php version with strict types
- Use latest phpstan version with level 9
- Use latest phpunit

## Getting started

Requirements: PHP 8.4+ and Composer.

```bash
composer install

# run static analysis (level 9)
composer stan

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

## Layout

- `public/index.php` — Slim application and routes
- `src/Http/` — fetcher abstraction with a `get(string $url): string` client
- `src/Weather/` — Open-Meteo client, weather DTO, WMO code descriptions
- `tests/` — PHPUnit tests (client hydration, DTO shape, code mapping)
