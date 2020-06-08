<?php

namespace Zenstruck\Governator\Store;

use Zenstruck\Governator\Key;
use Zenstruck\Governator\Quota;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryStore implements Store
{
    /** @var array<string, Quota> */
    private array $cache = [];

    public function hit(Key $key): Quota
    {
        $quota = $this->cache[(string) $key] ?? Quota::forKey($key);

        if (0 === $quota->resetsIn()) {
            $quota = Quota::forKey($key);
        }

        $this->cache[(string) $key] = $quota = $quota->addHit();

        return $quota;
    }

    public function reset(Key $key): void
    {
        unset($this->cache[(string) $key]);
    }
}
