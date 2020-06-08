<?php

namespace Zenstruck\Governator\Store;

use Predis\ClientInterface;
use Symfony\Component\Cache\Traits\RedisProxy;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisStore implements Store
{
    private $client;

    /**
     * @param \Redis|ClientInterface|RedisProxy $client
     */
    public function __construct($client)
    {
        if (!$client instanceof \Redis && !$client instanceof ClientInterface && !$client instanceof RedisProxy) {
            throw new \InvalidArgumentException(\sprintf('"%s()" expects parameter 1 to be Redis, Predis\ClientInterface, "%s" given.', __METHOD__, get_debug_type($client)));
        }

        $this->client = $client;
    }

    public function hit(Key $key): Counter
    {
        $results = $this->getResults($key);

        return new Counter($key->limit() - $results[2], $results[1]);
    }

    public function reset(Key $key): void
    {
        $this->client->del((string) $key);
    }

    private function getResults(Key $key): array
    {
        if (
            $this->client instanceof \Redis ||
            $this->client instanceof RedisProxy
        ) {
            return $this->client->eval(
                self::luaScript(), [
                    (string) $key,
                    microtime(true),
                    time(),
                    $key->ttl(),
                    $key->limit(),
                ],
                1
            );
        }

        return $this->client->eval(self::luaScript(), 1, (string) $key, microtime(true), time(), $key->ttl(), $key->limit());
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * @see https://github.com/laravel/framework/blob/6dee0732994fd1c03762f6f18dc02a630489fd43/src/Illuminate/Redis/Limiters/DurationLimiter.php#L125
     *
     * KEYS[1] - The limiter name
     * ARGV[1] - Current time in microseconds
     * ARGV[2] - Current time in seconds
     * ARGV[3] - Duration of the bucket
     * ARGV[4] - Allowed number of tasks
     */
    private static function luaScript(): string
    {
        return <<<'LUA'
            local function reset()
                redis.call('HMSET', KEYS[1], 'start', ARGV[2], 'end', ARGV[2] + ARGV[3], 'count', 1)
                return redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
            end
            if redis.call('EXISTS', KEYS[1]) == 0 then
                return {reset(), ARGV[2] + ARGV[3], ARGV[4] - 1}
            end
            if ARGV[1] >= redis.call('HGET', KEYS[1], 'start') and ARGV[1] <= redis.call('HGET', KEYS[1], 'end') then
                return {
                    tonumber(redis.call('HINCRBY', KEYS[1], 'count', 1)) <= tonumber(ARGV[4]),
                    redis.call('HGET', KEYS[1], 'end'),
                    ARGV[4] - redis.call('HGET', KEYS[1], 'count')
                }
            end
            return {reset(), ARGV[2] + ARGV[3], ARGV[4] - 1}
            LUA;
    }
}
