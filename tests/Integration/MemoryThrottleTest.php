<?php

namespace Zenstruck\Governator\Tests\Integration;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\MemoryStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryThrottleTest extends ThrottleTest
{
    protected function createStore(): Store
    {
        return new MemoryStore();
    }
}
