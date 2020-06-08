<?php

namespace Zenstruck\Governator\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\MemoryStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MemoryThrottleTest extends TestCase
{
    use ThrottleTests;

    protected static function createStore(): Store
    {
        return new MemoryStore();
    }
}
