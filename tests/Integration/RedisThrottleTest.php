<?php

namespace Zenstruck\Governator\Tests\Integration;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield RedisStore::class;
    }

    protected function createStore(): Store
    {
        $client = new \Redis();
        $client->connect('127.0.0.1');

        return new RedisStore($client);
    }
}
