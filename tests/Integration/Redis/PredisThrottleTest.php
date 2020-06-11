<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PredisThrottleTest extends BaseRedisThrottleTest
{
    public static function setUpBeforeClass(): void
    {
        if (!\getenv('REDIS_HOST')) {
            self::markTestSkipped('REDIS_HOST not configured.');
        }
    }

    protected function createConnection(): object
    {
        $redis = new \Predis\Client('tcp://'.\getenv('REDIS_HOST').':6379');
        $redis->connect();

        return $redis;
    }
}
