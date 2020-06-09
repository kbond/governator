<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Traits\RedisProxy;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;
use Zenstruck\Governator\Tests\Integration\ThrottleTest;

/**
 * @requires extension redis
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisProxyThrottleTest extends ThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\getenv('REDIS_HOST')) {
            self::markTestSkipped('REDIS_HOST not configured.');
        }
    }

    protected function createStore(): Store
    {
        $connection = RedisAdapter::createConnection('redis://'.\getenv('REDIS_HOST').'?lazy=true');

        if (!$connection instanceof RedisProxy) {
            throw new \RuntimeException('Expected instance of '.RedisProxy::class);
        }

        return new RedisStore($connection);
    }
}
