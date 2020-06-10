<?php

namespace Zenstruck\Governator\Tests\Unit\Store;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store\RedisStore;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RedisStoreTest extends TestCase
{
    /**
     * @test
     */
    public function must_instantiate_with_a_valid_connection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RedisStore(new \stdClass());
    }
}
