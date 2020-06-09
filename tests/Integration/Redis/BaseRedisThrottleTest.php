<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;
use Zenstruck\Governator\Tests\Integration\ThrottleTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BaseRedisThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield RedisStore::class;
    }

    protected function createStore(): Store
    {
        return new RedisStore($this->createConnection());
    }

    abstract protected function createConnection(): object;
}
