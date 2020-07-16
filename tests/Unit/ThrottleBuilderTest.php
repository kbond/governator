<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Exception\QuotaExceeded;
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
    public function can_call_acquire_directly(): void
    {
        $builder = ThrottleFactory::for('memory')->throttle('foo')->allow(2)->every(10);

        $builder->acquire();
        $builder->acquire();

        try {
            $builder->acquire();
        } catch (QuotaExceeded $e) {
            $this->assertSame(3, $e->hits());

            return;
        }

        $this->fail('Exception not thrown');
    }

    /**
     * @test
     */
    public function can_call_reset_directly(): void
    {
        $builder = ThrottleFactory::for('memory')->throttle('foo')->allow(5)->every(10);

        $builder->acquire();
        $builder->acquire();
        $builder->reset();

        $this->assertSame(1, $builder->acquire()->hits());
    }

    /**
     * @test
     */
    public function can_call_status_directly(): void
    {
        $builder = ThrottleFactory::for('memory')->throttle('foo')->allow(2)->every(10);

        $builder->hit();
        $builder->hit();
        $builder->hit();

        $this->assertSame(3, $builder->status()->hits());

        try {
            $builder->status()->check();
        } catch (QuotaExceeded $e) {
            $this->assertSame(3, $e->hits());

            return;
        }

        $this->fail('Exception not thrown');
    }

    /**
     * @test
     */
    public function can_call_hit_directly(): void
    {
        $builder = ThrottleFactory::for('memory')->throttle('foo')->allow(2)->every(10);

        $builder->hit();
        $builder->hit();
        $quota = $builder->hit();

        $this->assertSame(3, $quota->hits());
        $this->assertTrue($quota->hasBeenExceeded());
    }

    /**
     * @test
     */
    public function multiple_resources_are_converted_to_string(): void
    {
        $key = ThrottleFactory::for('memory')
            ->throttle('a', 'b')
            ->with('c', 'd')
            ->with('e')
            ->allow(5)
            ->every(60)
            ->acquire()
            ->key()
        ;

        $this->assertSame('abcde', $key->resource());
    }

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

    /**
     * @test
     */
    public function resource_is_required(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The resource for the throttle cannot be blank.');

        (new ThrottleBuilder(new ThrottleFactory(new MemoryStore())))->allow(10)->every(60)->create();
    }
}
