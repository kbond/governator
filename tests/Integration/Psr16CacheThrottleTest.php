<?php

namespace Zenstruck\Governator\Tests\Integration;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr16CacheStore;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr16CacheThrottleTest extends BaseThrottleTest
{
    protected function createStore(): Store
    {
        if (\class_exists(Psr16Cache::class)) {
            return new Psr16CacheStore(new Psr16Cache(new FilesystemAdapter()));
        }

        return new Psr16CacheStore(new FilesystemCache());
    }
}
