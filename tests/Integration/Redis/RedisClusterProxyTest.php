<?php

namespace Zenstruck\Governator\Tests\Integration\Redis;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Traits\RedisClusterProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisClusterProxyTest extends BaseRedisThrottleTest
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
        $hosts = \array_map(fn($host) => "host[{$host}]", \explode(' ', \getenv('REDIS_CLUSTER_HOSTS')));
        $connection = RedisAdapter::createConnection('redis:/?'.\implode('&', $hosts), ['lazy' => true, 'redis_cluster' => true]);

        if (!$connection instanceof RedisClusterProxy) {
            throw new \RuntimeException('Expected instance of '.RedisClusterProxy::class);
        }

        return $connection;
    }
}
