<?php

namespace Zenstruck\Governator\Tests\Integration;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr6CacheStore;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr6CacheThrottleTest extends BaseThrottleTest
{
    protected function createStore(): Store
    {
        return new Psr6CacheStore(new FilesystemAdapter());
    }
}
