<?php

declare(strict_types=1);

namespace App\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

use function array_key_exists;
use function array_walk;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function hash;
use function is_dir;
use function is_int;
use function is_readable;
use function is_writable;
use function mkdir;
use function preg_match;
use function serialize;
use function sprintf;
use function time;
use function unlink;
use function unserialize;

final class FileCache implements CacheInterface
{
    private const FILE_PATTERN = '*.cache';

    public function __construct(
        private readonly string $directory,
        private readonly int $defaultTtlSeconds = 300,
    ) {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cache directory "%s" could not be created', $this->directory));
        }

        if (!is_writable($this->directory)) {
            throw new RuntimeException(sprintf('Cache directory "%s" is not writable', $this->directory));
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->payload($key);

        if (null === $payload) {
            return $default;
        }

        if ($this->expired($payload)) {
            $this->purge($key);

            return $default;
        }

        return $payload['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttlSeconds = $this->ttlSeconds($ttl);

        return false !== file_put_contents(
            $this->filename($key),
            serialize([
                'expires_at' => time() + $ttlSeconds,
                'value' => $value,
            ]),
        );
    }

    public function delete(string $key): bool
    {
        $file = $this->filename($key);

        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '/' . self::FILE_PATTERN);

        if (false === $files) {
            return false;
        }

        array_walk($files, static function (string $file): void {
            @unlink($file);
        });

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set((string) $key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has(string $key): bool
    {
        $payload = $this->payload($key);

        if (null === $payload) {
            return false;
        }

        if ($this->expired($payload)) {
            $this->purge($key);

            return false;
        }

        return true;
    }

    private function purge(string $key): void
    {
        @unlink($this->filename($key));
    }

    /**
     * @return array{expires_at: int, value: mixed}|null
     */
    private function payload(string $key): ?array
    {
        $this->validateKey($key);
        $file = $this->filename($key);

        if (!is_readable($file)) {
            return null;
        }

        $raw = file_get_contents($file);

        if (false === $raw) {
            return null;
        }

        /** @var mixed $payload */
        $payload = @unserialize($raw);

        if (!is_array($payload) || !isset($payload['expires_at']) || !is_int($payload['expires_at']) || !array_key_exists('value', $payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function expired(array $payload): bool
    {
        return time() >= $payload['expires_at'];
    }

    private function ttlSeconds(null|int|DateInterval $ttl): int
    {
        if (null === $ttl) {
            return $this->defaultTtlSeconds;
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable('now');

        return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
    }

    private function filename(string $key): string
    {
        return sprintf('%s/%s.cache', $this->directory, hash('sha256', $key));
    }

    private function validateKey(string $key): void
    {
        if (1 !== preg_match('#^[A-Za-z0-9_.\-]+$#', $key)) {
            throw InvalidArgumentCacheException::forKey($key);
        }
    }
}
