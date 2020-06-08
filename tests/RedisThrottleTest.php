<?php

namespace Zenstruck\Governator\Tests;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield from parent::clockMockClasses();

        yield RedisStore::class;
    }

    protected static function createStore(): Store
    {
        $client = new \Redis();
        $client->connect('127.0.0.1');

        $client->flushAll();

        return new RedisStore($client);
    }
}
