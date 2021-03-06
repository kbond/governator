<?php

namespace Zenstruck\Governator\Store;

use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryStore implements Store
{
    /** @var array<string, Counter> */
    private array $cache = [];

    public function hit(Key $key): Counter
    {
        return $this->cache[(string) $key] = $this->status($key)->addHit();
    }

    public function status(Key $key): Counter
    {
        $counter = $this->cache[(string) $key] ?? $key->createCounter();

        if (0 === $counter->resetsIn()) {
            $counter = $key->createCounter();
        }

        return $counter;
    }

    public function reset(Key $key): void
    {
        unset($this->cache[(string) $key]);
    }
}
