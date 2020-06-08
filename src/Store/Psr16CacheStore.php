<?php

namespace Zenstruck\Governator\Store;

use Psr\SimpleCache\CacheInterface;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr16CacheStore implements Store
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function hit(Key $key): Counter
    {
        $counter = $this->cache->get((string) $key);

        if (!$counter instanceof Counter) {
            $counter = $key->createCounter();
        }

        $counter = $counter->addHit();

        $this->cache->set((string) $key, $counter, $counter->resetsIn());

        return $counter;
    }

    public function reset(Key $key): void
    {
        $this->cache->delete((string) $key);
    }
}
