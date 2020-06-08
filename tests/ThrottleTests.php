<?php

namespace Zenstruck\Governator\Tests;

use Zenstruck\Governator\RateLimitExceeded;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ThrottleTests
{
    /**
     * @test
     */
    public function can_hit_throttle(): void
    {
        $factory = self::factory();
        $rateLimit = $factory->throttle('foo', 5, 60)->hit();

        $this->assertSame(5, $rateLimit->limit());
        $this->assertSame(1, $rateLimit->hits());
        $this->assertSame(4, $rateLimit->remaining());
        $this->assertLessThanOrEqual(60, $rateLimit->resetsIn());
        $this->assertGreaterThanOrEqual(59, $rateLimit->resetsIn());

        $rateLimit = $factory->throttle('foo')->allow(5)->every(60)->hit();

        $this->assertSame(5, $rateLimit->limit());
        $this->assertSame(2, $rateLimit->hits());
        $this->assertSame(3, $rateLimit->remaining());
        $this->assertLessThanOrEqual(60, $rateLimit->resetsIn());
        $this->assertGreaterThanOrEqual(59, $rateLimit->resetsIn());
    }

    /**
     * @test
     */
    public function ensure_resets_after_ttl(): void
    {
        $factory = self::factory();
        $rateLimit = $factory->throttle('foo', 1, 3)->hit();

        $this->assertSame(1, $rateLimit->hits());
        $this->assertSame(0, $rateLimit->remaining());

        \sleep($rateLimit->resetsIn());

        $rateLimit = $factory->throttle('foo', 1, 3)->hit();
        $this->assertSame(1, $rateLimit->hits());
        $this->assertSame(0, $rateLimit->remaining());
    }

    /**
     * @test
     */
    public function exceeding_limit_throws_rate_limit_exceeded_exception(): void
    {
        $factory = self::factory();
        $factory->throttle('foo', 5, 60)->hit();
        $factory->throttle('foo', 5, 60)->hit();
        $factory->throttle('foo', 5, 60)->hit();
        $factory->throttle('foo', 5, 60)->hit();
        $factory->throttle('foo', 5, 60)->hit();

        try {
            $factory->throttle('foo', 5, 60)->hit();
        } catch (RateLimitExceeded $exception) {
            $this->assertSame(5, $exception->limit());
            $this->assertSame(6, $exception->hits());
            $this->assertSame(0, $exception->remaining());
            $this->assertLessThanOrEqual(60, $exception->resetsIn());
            $this->assertGreaterThanOrEqual(59, $exception->resetsIn());

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function allow_and_every_are_immutable(): void
    {
        $this->assertCount(3, \array_unique([
            \spl_object_id($throttle1 = self::factory()->throttle('foo')),
            \spl_object_id($throttle2 = $throttle1->every(2)),
            \spl_object_id($throttle3 = $throttle1->allow(5)),
        ]));
    }

    /**
     * @test
     */
    public function can_reset_throttle(): void
    {
        $throttle = self::factory()->throttle('foo', 5, 60);

        $this->assertSame(4, $throttle->hit()->remaining());
        $this->assertSame(3, $throttle->hit()->remaining());

        $throttle->reset();

        $this->assertSame(4, $throttle->hit()->remaining());
    }

    abstract protected static function createStore(): Store;

    private static function factory(): ThrottleFactory
    {
        return new ThrottleFactory(static::createStore());
    }
}
