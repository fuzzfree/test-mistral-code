<?php

declare(strict_types=1);

namespace Tests\Cache;

use App\Cache\FileCache;
use App\Cache\InvalidArgumentCacheException;
use DateInterval;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileCacheTest extends TestCase
{
    private string $directory;

    private FileCache $cache;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/file-cache-test-' . uniqid('', true);
        $this->cache = new FileCache($this->directory);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        /** @var list<string> $files */
        $files = glob($this->directory . '/*') ?: [];

        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function testSetAndGetRoundTrip(): void
    {
        $object = new \stdClass();
        $object->name = 'volos';

        self::assertTrue($this->cache->set('scalar', 42));
        self::assertTrue($this->cache->set('array', ['a' => 1]));
        self::assertTrue($this->cache->set('null', null));

        self::assertSame(42, $this->cache->get('scalar'));
        self::assertSame(['a' => 1], $this->cache->get('array'));
        self::assertNull($this->cache->get('null'));
        self::assertTrue($this->cache->has('null'));
    }

    public function testNestedDirectoryIsCreated(): void
    {
        $cache = new FileCache($this->directory . '/nested/deeper');

        self::assertTrue($cache->set('k', 'v'));
        self::assertSame('v', $cache->get('k'));
    }

    public function testMissingDirectoryIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not be created');

        new FileCache('/proc/definitely/not/writable/path');
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        self::assertNull($this->cache->get('unknown'));
        self::assertSame('fallback', $this->cache->get('unknown', 'fallback'));
        self::assertFalse($this->cache->has('unknown'));
    }

    public function testZeroTtlExpiresImmediately(): void
    {
        self::assertTrue($this->cache->set('short', 'v', 0));

        self::assertSame('fallback', $this->cache->get('short', 'fallback'));
        self::assertFalse($this->cache->has('short'));
    }

    public function testDateIntervalTtl(): void
    {
        self::assertTrue($this->cache->set('interval', 'v', new DateInterval('PT5M')));

        self::assertSame('v', $this->cache->get('interval'));
    }

    public function testDelete(): void
    {
        $this->cache->set('gone', 'v');

        self::assertTrue($this->cache->delete('gone'));
        self::assertNull($this->cache->get('gone'));
        self::assertTrue($this->cache->delete('gone'));
    }

    public function testClear(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        self::assertTrue($this->cache->clear());

        self::assertNull($this->cache->get('a'));
        self::assertNull($this->cache->get('b'));
    }

    public function testMultipleOperations(): void
    {
        self::assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2]));

        self::assertSame(['a' => 1, 'b' => 2], $this->cache->getMultiple(['a', 'b']));
        self::assertSame(['a' => 1, 'missing' => 'd'], $this->cache->getMultiple(['a', 'missing'], 'd'));

        self::assertTrue($this->cache->deleteMultiple(['a', 'b']));
        self::assertNull($this->cache->get('a'));
    }

    public function testDefaultTtlIsApplied(): void
    {
        $cache = new FileCache($this->directory, 0);

        $cache->set('k', 'v');

        self::assertNull($cache->get('k'));
    }

    public function testInvalidKeyIsRejected(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage('is invalid');

        $this->cache->get('invalid/key');
    }

    public function testCorruptedFileIsTreatedAsMiss(): void
    {
        $this->cache->set('corrupt', 'v');

        /** @var list<string> $files */
        $files = glob($this->directory . '/*.cache') ?: [];
        file_put_contents($files[0], 'not-serializable');

        self::assertNull($this->cache->get('corrupt'));
    }
}
