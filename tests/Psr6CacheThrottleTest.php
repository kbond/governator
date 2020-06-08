<?php

namespace Zenstruck\Governator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\Psr6CacheStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr6CacheThrottleTest extends TestCase
{
    use ThrottleTests;

    protected static function createStore(): Store
    {
        return new Psr6CacheStore(new ArrayAdapter());
    }
}
