<?php

namespace Zenstruck\Governator\Store;

use Psr\Cache\CacheItemPoolInterface;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\RateLimit;
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

    public function hit(Key $key): RateLimit
    {
        $item = $this->cache->getItem((string) $key);
        $rateLimit = $item->get();

        if (!$rateLimit instanceof RateLimit) {
            $rateLimit = RateLimit::forKey($key);
            $item->expiresAt($rateLimit->resetsAt());
        }

        $this->cache->save($item->set($rateLimit = $rateLimit->addHit()));

        return $rateLimit;
    }

    public function reset(Key $key): void
    {
        $this->cache->deleteItem((string) $key);
    }
}
