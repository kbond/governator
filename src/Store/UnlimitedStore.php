<?php

namespace Zenstruck\Governator\Store;

use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnlimitedStore implements Store
{
    public function hit(Key $key): Counter
    {
        return new Counter(1, time() + $key->ttl());
    }

    public function status(Key $key): Counter
    {
        return $key->createCounter();
    }

    public function reset(Key $key): void
    {
        // noop
    }
}
