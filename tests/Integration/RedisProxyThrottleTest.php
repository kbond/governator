<?php

namespace Zenstruck\Governator\Tests\Integration;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Traits\RedisProxy;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisProxyThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield RedisStore::class;
    }

    protected function createStore(): Store
    {
        $connection = RedisAdapter::createConnection('redis://127.0.0.1?lazy=true');

        if (!$connection instanceof RedisProxy) {
            throw new \RuntimeException('Expected instance of RedisProxy');
        }

        return new RedisStore($connection);
    }
}
