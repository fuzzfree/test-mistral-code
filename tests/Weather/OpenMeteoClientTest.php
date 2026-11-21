<?php

declare(strict_types=1);

namespace Tests\Weather;

use App\Weather\OpenMeteoClient;
use App\Weather\WeatherCodes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenMeteoClientTest extends TestCase
{
    private const FIXTURE = <<<'JSON'
        {
            "latitude": 39.3622,
            "longitude": 22.9421,
            "timezone": "Europe/Athens",
            "current": {
                "time": "2026-02-14T12:15",
                "temperature_2m": 13.4,
                "apparent_temperature": 12.1,
                "relative_humidity_2m": 71,
                "is_day": 1,
                "precipitation": 0.2,
                "weather_code": 80,
                "wind_speed_10m": 18.34,
                "wind_direction_10m": 292
            }
        }
        JSON;

    public function testVolosCurrentWeatherHydratesResponse(): void
    {
        $fetcher = new FakeHttpFetcher();
        $client = new OpenMeteoClient($fetcher);
        $fetcher->responses[$this->urlFor(OpenMeteoClient::VOLOS_LATITUDE, OpenMeteoClient::VOLOS_LONGITUDE)] = self::FIXTURE;

        $weather = $client->volosCurrentWeather();

        self::assertSame(39.3622, $weather->latitude);
        self::assertSame(22.9421, $weather->longitude);
        self::assertSame('Europe/Athens', $weather->timezone);
        self::assertSame('2026-02-14T12:15', $weather->time);
        self::assertSame(13.4, $weather->temperatureC);
        self::assertSame(12.1, $weather->apparentTemperatureC);
        self::assertSame(71, $weather->relativeHumidityPercent);
        self::assertSame(0.2, $weather->precipitationMm);
        self::assertSame(80, $weather->weatherCode);
        self::assertSame('Slight rain showers', $weather->description);
        self::assertTrue($weather->isDay);
        self::assertSame(18.3, $weather->windSpeedKmh);
        self::assertSame(292, $weather->windDirectionDeg);
    }

    public function testInvalidJsonIsRejected(): void
    {
        $fetcher = new FakeHttpFetcher();
        $client = new OpenMeteoClient($fetcher);
        $fetcher->responses[$this->urlFor(39.0, 22.0)] = 'not json';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid JSON');

        $client->currentWeather(39.0, 22.0);
    }

    public function testMissingCurrentSectionIsRejected(): void
    {
        $fetcher = new FakeHttpFetcher();
        $client = new OpenMeteoClient($fetcher);
        $fetcher->responses[$this->urlFor(39.0, 22.0)] = '{"latitude": 39.0}';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing the "current" section');

        $client->currentWeather(39.0, 22.0);
    }

    public function testMissingFieldIsRejected(): void
    {
        $fetcher = new FakeHttpFetcher();
        $client = new OpenMeteoClient($fetcher);
        $fetcher->responses[$this->urlFor(39.0, 22.0)] = '{"latitude": 1, "longitude": 2, "timezone": "UTC", "current": {"time": "t"}}';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing "temperature_2m"');

        $client->currentWeather(39.0, 22.0);
    }

    #[DataProvider('describeProvider')]
    public function testDescribe(int $code, string $expected): void
    {
        self::assertSame($expected, WeatherCodes::describe($code));
    }

    /** @return array<string, array{0: int, 1: string}> */
    public static function describeProvider(): array
    {
        return [
            'clear' => [0, 'Clear sky'],
            'snow grains' => [77, 'Snow grains'],
            'thunderstorm with heavy hail' => [99, 'Thunderstorm with heavy hail'],
            'unknown' => [42, 'Unknown'],
        ];
    }

    private function urlFor(float $latitude, float $longitude): string
    {
        return sprintf(
            'https://api.open-meteo.com/v1/forecast?current=temperature_2m%%2Capparent_temperature%%2Crelative_humidity_2m%%2Cis_day%%2Cprecipitation%%2Cweather_code%%2Cwind_speed_10m%%2Cwind_direction_10m&timezone=Europe%%2FAthens&latitude=%F&longitude=%F',
            $latitude,
            $longitude,
        );
    }
}
