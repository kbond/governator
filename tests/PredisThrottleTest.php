<?php

namespace Zenstruck\Governator\Tests;

use Predis\Client;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\Store\RedisStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PredisThrottleTest extends ThrottleTest
{
    protected static function clockMockClasses(): iterable
    {
        yield from parent::clockMockClasses();

        yield RedisStore::class;
    }

    protected static function createStore(): Store
    {
        return new RedisStore(new Client());
    }
}
