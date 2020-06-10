<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\ThrottleBuilder;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleBuilderTest extends TestCase
{
    /**
     * @test
     */
    public function limit_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('You must set a "Limit" for the throttle via "%s::allow($limit)"', ThrottleBuilder::class));

        (new ThrottleBuilder(new ThrottleFactory(new MemoryStore()), 'foo'))->every(10)->create();
    }

    /**
     * @test
     */
    public function ttl_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('You must set a "TTL" for the throttle via "%s::every($ttl)"', ThrottleBuilder::class));

        (new ThrottleBuilder(new ThrottleFactory(new MemoryStore()), 'foo'))->allow(10)->create();
    }
}
