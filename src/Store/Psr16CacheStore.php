<?php

namespace Zenstruck\Governator\Store;

use Psr\SimpleCache\CacheInterface;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\RateLimit;
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

    public function hit(Key $key): RateLimit
    {
        $rateLimit = $this->cache->get((string) $key);

        if (!$rateLimit instanceof RateLimit) {
            $rateLimit = RateLimit::forKey($key);
        }

        $rateLimit = $rateLimit->addHit();

        $this->cache->set((string) $key, $rateLimit, $rateLimit->resetsIn());

        return $rateLimit;
    }

    public function reset(Key $key): void
    {
        $this->cache->delete((string) $key);
    }
}
