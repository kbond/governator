<?php

namespace Zenstruck\Governator\Store;

use Psr\Cache\CacheItemPoolInterface;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr6CacheStore implements Store
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function hit(Key $key): Counter
    {
        $item = $this->cache->getItem((string) $key);
        $counter = $item->get();

        if (!$counter instanceof Counter) {
            $counter = $key->createCounter();
        }

        $item->set($counter = $counter->addHit());
        $item->expiresAfter($counter->resetsIn());
        $this->cache->save($item);

        return $counter;
    }

    public function reset(Key $key): void
    {
        $this->cache->deleteItem((string) $key);
    }
}
