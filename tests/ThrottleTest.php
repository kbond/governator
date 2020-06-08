<?php

namespace Zenstruck\Governator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ThrottleTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        ClockMock::register(static::class);

        foreach (static::clockMockClasses() as $class) {
            ClockMock::register($class);
        }

        ClockMock::withClockMock(true);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        ClockMock::withClockMock(false);
    }

    /**
     * @test
     */
    public function can_hit_throttle(): void
    {
        $factory = self::factory();
        $quota = $factory->throttle('foo', 5, 60)->hit();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        $quota = $factory->throttle('foo')->allow(5)->every(60)->hit();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(2, $quota->hits());
        $this->assertSame(3, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function ensure_resets_after_ttl(): void
    {
        $factory = self::factory();
        $quota = $factory->throttle('foo', 2, 2)->hit();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        $quota = $factory->throttle('foo', 2, 2)->hit();

        $this->assertSame(2, $quota->hits());
        $this->assertSame(0, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        sleep($quota->resetsIn());

        $quota = $factory->throttle('foo', 2, 2)->hit();
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function exceeding_limit_throws_rate_limit_exceeded_exception(): void
    {
        $factory = self::factory();
        $factory->throttle('foo', 5, 2)->hit();
        $factory->throttle('foo', 5, 2)->hit();
        $factory->throttle('foo', 5, 2)->hit();
        $factory->throttle('foo', 5, 2)->hit();
        $factory->throttle('foo', 5, 2)->hit();

        try {
            $factory->throttle('foo', 5, 2)->hit();
        } catch (QuotaExceeded $exception) {
            $this->assertSame(5, $exception->limit());
            $this->assertSame(6, $exception->hits());
            $this->assertSame(0, $exception->remaining());
            $this->assertSame(2, $exception->resetsIn());

            sleep($exception->resetsIn());

            $quota = $factory->throttle('foo', 5, 2)->hit();

            $this->assertSame(5, $quota->limit());
            $this->assertSame(1, $quota->hits());
            $this->assertSame(4, $quota->remaining());
            $this->assertSame(2, $quota->resetsIn());

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

    protected static function clockMockClasses(): iterable
    {
        yield Key::class;
        yield Counter::class;
    }

    private static function factory(): ThrottleFactory
    {
        return new ThrottleFactory(static::createStore());
    }
}
