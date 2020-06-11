<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

/**
 * @requires extension redis
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisClusterThrottleTest extends BaseRedisThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\class_exists('RedisCluster')) {
            self::markTestSkipped('The RedisCluster class is required.');
        }

        if (!\getenv('REDIS_CLUSTER_HOSTS')) {
            self::markTestSkipped('REDIS_CLUSTER_HOSTS env var is not defined.');
        }
    }

    protected function createConnection(): object
    {
        return new \RedisCluster(null, \explode(' ', \getenv('REDIS_CLUSTER_HOSTS')));
    }
}
