<?php

namespace Zenstruck\Governator\Store;

use Psr\Cache\CacheItemInterface;
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
        $item = $this->getNormalizedItem($key);

        /** @var Counter $counter */
        $counter = $item->get()->addHit();

        $item->set($counter);
        $item->expiresAfter($counter->resetsIn());
        $this->cache->save($item);

        return $counter;
    }

    public function status(Key $key): Counter
    {
        return $this->getNormalizedItem($key)->get();
    }

    public function reset(Key $key): void
    {
        $this->cache->deleteItem((string) $key);
    }

    private function getNormalizedItem(Key $key): CacheItemInterface
    {
        $item = $this->cache->getItem((string) $key);

        if (!$item->get() instanceof Counter) {
            $item->set($key->createCounter());
        }

        return $item;
    }
}
