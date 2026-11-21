<?php

declare(strict_types=1);

namespace App\Weather;

use JsonSerializable;

final class Weather implements JsonSerializable
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $timezone,
        public readonly string $time,
        public readonly float $temperatureC,
        public readonly float $apparentTemperatureC,
        public readonly int $relativeHumidityPercent,
        public readonly float $precipitationMm,
        public readonly int $weatherCode,
        public readonly string $description,
        public readonly bool $isDay,
        public readonly float $windSpeedKmh,
        public readonly int $windDirectionDeg,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'time' => $this->time,
            'current' => [
                'temperature_c' => $this->temperatureC,
                'apparent_temperature_c' => $this->apparentTemperatureC,
                'relative_humidity_percent' => $this->relativeHumidityPercent,
                'precipitation_mm' => $this->precipitationMm,
                'weather_code' => $this->weatherCode,
                'description' => $this->description,
                'is_day' => $this->isDay,
                'wind_speed_kmh' => $this->windSpeedKmh,
                'wind_direction_deg' => $this->windDirectionDeg,
            ],
        ];
    }
}
