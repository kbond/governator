<?php

namespace Zenstruck\Governator\Store;

use Zenstruck\Governator\Key;
use Zenstruck\Governator\RateLimit;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryStore implements Store
{
    /** @var array<string, RateLimit> */
    private array $cache = [];

    public function hit(Key $key): RateLimit
    {
        $rateLimit = $this->cache[(string) $key] ?? RateLimit::forKey($key);

        if (0 === $rateLimit->resetsIn()) {
            $rateLimit = RateLimit::forKey($key);
        }

        $this->cache[(string) $key] = $rateLimit = $rateLimit->addHit();

        return $rateLimit;
    }

    public function reset(Key $key): void
    {
        unset($this->cache[(string) $key]);
    }
}
