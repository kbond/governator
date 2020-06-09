<?php

namespace Zenstruck\Governator\Tests\Integration;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr6CacheStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr6CacheThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield ArrayAdapter::class;
        yield CacheItem::class;
    }

    protected function createStore(): Store
    {
        return new Psr6CacheStore(new ArrayAdapter());
    }
}
