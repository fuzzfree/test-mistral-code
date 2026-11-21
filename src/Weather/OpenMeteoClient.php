<?php

declare(strict_types=1);

namespace App\Weather;

use App\Http\HttpFetcher;
use JsonException;
use RuntimeException;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function json_decode;
use function round;
use function sprintf;
use function trim;
use const JSON_THROW_ON_ERROR;

final class OpenMeteoClient
{
    public const VOLOS_LATITUDE = 39.3622;

    public const VOLOS_LONGITUDE = 22.9421;

    private const API_URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(private readonly HttpFetcher $fetcher)
    {
    }

    public function volosCurrentWeather(): Weather
    {
        return $this->currentWeather(self::VOLOS_LATITUDE, self::VOLOS_LONGITUDE);
    }

    public function currentWeather(float $latitude, float $longitude): Weather
    {
        $url = sprintf(
            '%s?current=temperature_2m%%2Capparent_temperature%%2Crelative_humidity_2m%%2Cis_day%%2Cprecipitation%%2Cweather_code%%2Cwind_speed_10m%%2Cwind_direction_10m&timezone=Europe%%2FAthens&latitude=%F&longitude=%F',
            self::API_URL,
            $latitude,
            $longitude,
        );

        return $this->hydrate($this->fetcher->get($url));
    }

    private function hydrate(string $payload): Weather
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode(trim($payload), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Open-Meteo returned invalid JSON', 0, $exception);
        }

        if (!is_array($decoded) || !array_key_exists('current', $decoded) || !is_array($decoded['current'])) {
            throw new RuntimeException('Open-Meteo response is missing the "current" section');
        }

        /** @var array<string, mixed> $current */
        $current = $decoded['current'];

        $latitude = self::requiredFloat($decoded, 'latitude');
        $longitude = self::requiredFloat($decoded, 'longitude');
        $timezone = self::requiredString($decoded, 'timezone');

        return new Weather(
            latitude: $latitude,
            longitude: $longitude,
            timezone: $timezone,
            time: self::requiredString($current, 'time'),
            temperatureC: self::requiredFloat($current, 'temperature_2m'),
            apparentTemperatureC: self::requiredFloat($current, 'apparent_temperature'),
            relativeHumidityPercent: self::requiredInt($current, 'relative_humidity_2m'),
            precipitationMm: self::requiredFloat($current, 'precipitation'),
            weatherCode: self::requiredInt($current, 'weather_code'),
            description: WeatherCodes::describe(self::requiredInt($current, 'weather_code')),
            isDay: 1 === self::requiredInt($current, 'is_day'),
            windSpeedKmh: round(self::requiredFloat($current, 'wind_speed_10m'), 1),
            windDirectionDeg: self::requiredInt($current, 'wind_direction_10m'),
        );
    }

    /** @param array<string, mixed> $payload */
    private static function requiredFloat(array $payload, string $key): float
    {
        $raw = self::required($payload, $key);

        if (!is_numeric($raw)) {
            throw new RuntimeException(sprintf('Open-Meteo value "%s" is not numeric', $key));
        }

        return (float) $raw;
    }

    /** @param array<string, mixed> $payload */
    private static function requiredInt(array $payload, string $key): int
    {
        $raw = self::required($payload, $key);

        if (is_bool($raw) || !is_numeric($raw)) {
            throw new RuntimeException(sprintf('Open-Meteo value "%s" is not numeric', $key));
        }

        return (int) $raw;
    }

    /** @param array<string, mixed> $payload */
    private static function requiredString(array $payload, string $key): string
    {
        $raw = self::required($payload, $key);

        if (!is_string($raw)) {
            throw new RuntimeException(sprintf('Open-Meteo value "%s" is not a string', $key));
        }

        return $raw;
    }

    /** @param array<string, mixed> $payload */
    private static function required(array $payload, string $key): mixed
    {
        if (!array_key_exists($key, $payload)) {
            throw new RuntimeException(sprintf('Open-Meteo response is missing "%s"', $key));
        }

        return $payload[$key];
    }
}
