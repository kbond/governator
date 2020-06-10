<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Traits\RedisClusterProxy;
use Symfony\Component\Cache\Traits\RedisProxy;
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\Store\Psr16CacheStore;
use Zenstruck\Governator\Store\Psr6CacheStore;
use Zenstruck\Governator\Store\RedisStore;
use Zenstruck\Governator\Store\UnlimitedStore;
use Zenstruck\Governator\StoreFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoreFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider connectionProvider
     */
    public function can_create_for_connection($connection, $expectedClass): void
    {
        $this->assertInstanceOf($expectedClass, StoreFactory::create($connection));
    }

    public function connectionProvider(): iterable
    {
        yield ['memory', MemoryStore::class];
        yield ['unlimited', UnlimitedStore::class];
        yield [new MemoryStore(), MemoryStore::class];
        yield [$this->createMock(CacheItemPoolInterface::class), Psr6CacheStore::class];
        yield [$this->createMock(CacheInterface::class), Psr16CacheStore::class];
        yield [$this->createMock(\Redis::class), RedisStore::class];
        yield [$this->createMock(\RedisArray::class), RedisStore::class];
        yield [$this->createMock(\RedisCluster::class), RedisStore::class];
        yield [$this->createMock(ClientInterface::class), RedisStore::class];
        yield [$this->createMock(RedisProxy::class), RedisStore::class];

        if (\class_exists(RedisClusterProxy::class)) {
            yield [$this->createMock(RedisClusterProxy::class), RedisStore::class];
        }
    }

    /**
     * @test
     */
    public function connection_must_be_string_or_object(): void
    {
        $this->expectException(\TypeError::class);

        StoreFactory::create(['array']);
    }

    /**
     * @test
     */
    public function unsupported_string_connection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StoreFactory::create('invalid');
    }

    /**
     * @test
     */
    public function unsupported_object_connection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StoreFactory::create(new \stdClass());
    }
}
