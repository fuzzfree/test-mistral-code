<?php

declare(strict_types=1);

namespace App\Weather;

use Psr\SimpleCache\CacheInterface;

final class CachedOpenMeteoClient
{
    public const CACHE_KEY = 'volos-current-weather';

    public const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly OpenMeteoClient $client,
        private readonly CacheInterface $cache,
        private readonly int $ttlSeconds = self::CACHE_TTL_SECONDS,
    ) {
    }

    public function volosCurrentWeather(): Weather
    {
        $cached = $this->cache->get(self::CACHE_KEY);

        if ($cached instanceof Weather) {
            return $cached;
        }

        $weather = $this->client->volosCurrentWeather();
        $this->cache->set(self::CACHE_KEY, $weather, $this->ttlSeconds);

        return $weather;
    }
}
