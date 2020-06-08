<?php

namespace Zenstruck\Governator\Store;

use Psr\SimpleCache\CacheInterface;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Quota;
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

    public function hit(Key $key): Quota
    {
        $quota = $this->cache->get((string) $key);

        if (!$quota instanceof Quota) {
            $quota = Quota::forKey($key);
        }

        $quota = $quota->addHit();

        $this->cache->set((string) $key, $quota, $quota->resetsIn());

        return $quota;
    }

    public function reset(Key $key): void
    {
        $this->cache->delete((string) $key);
    }
}
