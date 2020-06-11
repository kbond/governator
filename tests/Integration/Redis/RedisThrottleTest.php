<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

/**
 * @requires extension redis
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisThrottleTest extends BaseRedisThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\getenv('REDIS_HOST')) {
            self::markTestSkipped('REDIS_HOST not configured.');
        }
    }

    protected function createConnection(): object
    {
        $redis = new \Redis();
        $redis->connect(\getenv('REDIS_HOST'));

        return $redis;
    }
}
