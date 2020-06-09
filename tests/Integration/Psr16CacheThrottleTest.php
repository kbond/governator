<?php

namespace Zenstruck\Governator\Tests\Integration;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Psr16Cache;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr16CacheStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr16CacheThrottleTest extends ThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\class_exists(Psr16Cache::class)) {
            self::markTestSkipped('Psr16Cache not available.');
        }
    }

    protected static function clockMockClasses(): iterable
    {
        yield FilesystemAdapter::class;
        yield 'Symfony\Component\Cache\Traits\FilesystemTrait';
        yield CacheItem::class;
    }

    protected function createStore(): Store
    {
        return new Psr16CacheStore(new Psr16Cache(new FilesystemAdapter()));
    }
}
