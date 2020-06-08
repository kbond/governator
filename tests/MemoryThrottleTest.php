<?php

namespace Zenstruck\Governator\Tests;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\MemoryStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryThrottleTest extends ThrottleTest
{
    protected static function createStore(): Store
    {
        return new MemoryStore();
    }
}
