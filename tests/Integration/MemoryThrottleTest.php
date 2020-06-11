<?php

namespace Zenstruck\Governator\Tests\Integration;

use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\MemoryStore;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryThrottleTest extends BaseThrottleTest
{
    protected function createStore(): Store
    {
        return new MemoryStore();
    }
}
