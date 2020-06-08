<?php

namespace Zenstruck\Governator\Store;

use Psr\Cache\CacheItemPoolInterface;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Quota;
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

    public function hit(Key $key): Quota
    {
        $item = $this->cache->getItem((string) $key);
        $quota = $item->get();

        if (!$quota instanceof Quota) {
            $quota = Quota::forKey($key);
            $item->expiresAt($quota->resetsAt());
        }

        $this->cache->save($item->set($quota = $quota->addHit()));

        return $quota;
    }

    public function reset(Key $key): void
    {
        $this->cache->deleteItem((string) $key);
    }
}
