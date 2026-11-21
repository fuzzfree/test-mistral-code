<?php

declare(strict_types=1);

namespace Tests\Weather;

use App\Cache\FileCache;
use App\Weather\CachedOpenMeteoClient;
use App\Weather\OpenMeteoClient;
use App\Weather\Weather;
use PHPUnit\Framework\TestCase;

final class CachedOpenMeteoClientTest extends TestCase
{
    private string $cacheDirectory = '';

    protected function tearDown(): void
    {
        if ('' === $this->cacheDirectory || !is_dir($this->cacheDirectory)) {
            return;
        }

        /** @var list<string> $files */
        $files = glob($this->cacheDirectory . '/*.cache') ?: [];

        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->cacheDirectory);
    }

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

    public function testFetchesAndCachesOnMiss(): void
    {
        $fetcher = new FakeHttpFetcher();
        $fetcher->responses[$this->urlFor()] = self::FIXTURE;
        $cache = new FileCache($this->cacheDir());
        $client = new CachedOpenMeteoClient(new OpenMeteoClient($fetcher), $cache);

        $weather = $client->volosCurrentWeather();

        self::assertSame(13.4, $weather->temperatureC);
        self::assertCount(1, $fetcher->requests);
    }

    public function testSecondCallIsServedFromCache(): void
    {
        $fetcher = new FakeHttpFetcher();
        $fetcher->responses[$this->urlFor()] = self::FIXTURE;
        $cache = new FileCache($this->cacheDir());
        $client = new CachedOpenMeteoClient(new OpenMeteoClient($fetcher), $cache);

        $first = $client->volosCurrentWeather();
        $second = $client->volosCurrentWeather();

        self::assertEquals($first, $second);
        self::assertCount(1, $fetcher->requests);
    }

    public function testExpiredEntryTriggersRefetch(): void
    {
        $fetcher = new FakeHttpFetcher();
        $fetcher->responses[$this->urlFor()] = self::FIXTURE;
        $cache = new FileCache($this->cacheDir());
        $client = new CachedOpenMeteoClient(new OpenMeteoClient($fetcher), $cache, 0);

        $client->volosCurrentWeather();
        $client->volosCurrentWeather();

        self::assertCount(2, $fetcher->requests);
    }

    public function testHitReturnsCachedWeatherWithoutFetching(): void
    {
        $fetcher = new FakeHttpFetcher();
        $cache = new FileCache($this->cacheDir());
        $cached = new Weather(
            latitude: 39.3622,
            longitude: 22.9421,
            timezone: 'Europe/Athens',
            time: '2026-02-14T12:15',
            temperatureC: 1.0,
            apparentTemperatureC: 1.0,
            relativeHumidityPercent: 50,
            precipitationMm: 0.0,
            weatherCode: 0,
            description: 'Clear sky',
            isDay: true,
            windSpeedKmh: 1.0,
            windDirectionDeg: 1,
        );
        $cache->set(CachedOpenMeteoClient::CACHE_KEY, $cached);
        $client = new CachedOpenMeteoClient(new OpenMeteoClient($fetcher), $cache);

        self::assertEquals($cached, $client->volosCurrentWeather());
        self::assertCount(0, $fetcher->requests);
    }

    public function testCustomTtlIsPassedToCache(): void
    {
        $fetcher = new FakeHttpFetcher();
        $fetcher->responses[$this->urlFor()] = self::FIXTURE;
        $cache = new FileCache($this->cacheDir());
        $client = new CachedOpenMeteoClient(new OpenMeteoClient($fetcher), $cache, 0);

        $client->volosCurrentWeather();

        self::assertFalse($cache->has(CachedOpenMeteoClient::CACHE_KEY));
        self::assertNull($cache->get(CachedOpenMeteoClient::CACHE_KEY));
    }

    private function cacheDir(): string
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/cached-client-test-' . uniqid('', true);

        return $this->cacheDirectory;
    }

    private function urlFor(): string
    {
        return sprintf(
            'https://api.open-meteo.com/v1/forecast?current=temperature_2m%%2Capparent_temperature%%2Crelative_humidity_2m%%2Cis_day%%2Cprecipitation%%2Cweather_code%%2Cwind_speed_10m%%2Cwind_direction_10m&timezone=Europe%%2FAthens&latitude=%F&longitude=%F',
            OpenMeteoClient::VOLOS_LATITUDE,
            OpenMeteoClient::VOLOS_LONGITUDE,
        );
    }
}
