<?php

namespace Zenstruck\Governator\Tests\Integration;

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
        yield RedisStore::class;
    }

    protected function createStore(): Store
    {
        return new RedisStore(new Client());
    }
}
