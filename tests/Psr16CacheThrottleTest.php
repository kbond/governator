<?php

namespace Zenstruck\Governator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr16CacheStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr16CacheThrottleTest extends TestCase
{
    use ThrottleTests;

    protected static function createStore(): Store
    {
        return new Psr16CacheStore(new Psr16Cache(new ArrayAdapter()));
    }
}
