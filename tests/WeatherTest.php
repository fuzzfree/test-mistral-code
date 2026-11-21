<?php

declare(strict_types=1);

namespace Tests;

use App\Weather\Weather;
use PHPUnit\Framework\TestCase;

final class WeatherTest extends TestCase
{
    public function testJsonSerializableShape(): void
    {
        $weather = new Weather(
            latitude: 39.3622,
            longitude: 22.9421,
            timezone: 'Europe/Athens',
            time: '2026-02-14T12:15',
            temperatureC: 13.4,
            apparentTemperatureC: 12.1,
            relativeHumidityPercent: 71,
            precipitationMm: 0.0,
            weatherCode: 0,
            description: 'Clear sky',
            isDay: false,
            windSpeedKmh: 5.0,
            windDirectionDeg: 180,
        );

        self::assertSame(
            [
                'latitude' => 39.3622,
                'longitude' => 22.9421,
                'timezone' => 'Europe/Athens',
                'time' => '2026-02-14T12:15',
                'current' => [
                    'temperature_c' => 13.4,
                    'apparent_temperature_c' => 12.1,
                    'relative_humidity_percent' => 71,
                    'precipitation_mm' => 0.0,
                    'weather_code' => 0,
                    'description' => 'Clear sky',
                    'is_day' => false,
                    'wind_speed_kmh' => 5.0,
                    'wind_direction_deg' => 180,
                ],
            ],
            $weather->jsonSerialize(),
        );
    }
}
