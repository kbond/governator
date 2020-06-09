<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

/**
 * @requires extension redis
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisArrayThrottleTest extends BaseRedisThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\class_exists('RedisArray')) {
            self::markTestSkipped('The RedisArray class is required.');
        }

        if (!\getenv('REDIS_HOST')) {
            self::markTestSkipped('REDIS_HOST not configured.');
        }
    }

    protected function createConnection(): object
    {
        return new \RedisArray([\getenv('REDIS_HOST')]);
    }
}
