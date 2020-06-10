<?php

namespace Zenstruck\Governator;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Traits\RedisClusterProxy;
use Symfony\Component\Cache\Traits\RedisProxy;
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\Store\Psr16CacheStore;
use Zenstruck\Governator\Store\Psr6CacheStore;
use Zenstruck\Governator\Store\RedisStore;
use Zenstruck\Governator\Store\UnlimitedStore;

/**
 * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Lock/Store/StoreFactory.php
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoreFactory
{
    /**
     * @param string|object $connection
     */
    public static function create($connection): Store
    {
        if (!\is_string($connection) && !\is_object($connection)) {
            throw new \TypeError(\sprintf('Argument 1 passed to "%s()" must be a string or a connection object, "%s" given.', __METHOD__, \gettype($connection)));
        }

        switch (true) {
            case $connection instanceof Store:
                return $connection;
            case $connection instanceof CacheItemPoolInterface:
                return new Psr6CacheStore($connection);
            case $connection instanceof CacheInterface:
                return new Psr16CacheStore($connection);
            case $connection instanceof \Redis:
            case $connection instanceof \RedisArray:
            case $connection instanceof \RedisCluster:
            case $connection instanceof \Predis\ClientInterface:
            case $connection instanceof RedisProxy:
            case $connection instanceof RedisClusterProxy:
                return new RedisStore($connection);
            case !\is_string($connection):
                throw new \InvalidArgumentException(\sprintf('Unsupported Connection: "%s".', \get_class($connection)));
            case 'memory' === $connection:
                return new MemoryStore();
            case 'unlimited' === $connection:
                return new UnlimitedStore();
        }

        throw new \InvalidArgumentException(\sprintf('Unsupported Connection: "%s".', $connection));
    }
}
