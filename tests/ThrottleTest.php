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
        $resource = 'foo';
        $limit = 5;
        $ttl = 60;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $quota = $factory->create($resource, $limit, $ttl)->hit();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->create()->hit();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(2, $quota->hits());
        $this->assertSame(3, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->hit();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(3, $quota->hits());
        $this->assertSame(2, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function ensure_resets_after_ttl(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $quota = $factory->create($resource, $limit, $ttl)->hit();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        $quota = $factory->create($resource, $limit, $ttl)->hit();

        $this->assertSame(2, $quota->hits());
        $this->assertSame(0, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        sleep($quota->resetsIn());

        $quota = $factory->create($resource, $limit, $ttl)->hit();
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function exceeding_limit_throws_rate_limit_exceeded_exception(): void
    {
        $resource = 'foo';
        $limit = 5;
        $ttl = 2;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();

        try {
            $factory->create($resource, $limit, $ttl)->hit();
        } catch (QuotaExceeded $exception) {
            $this->assertSame(5, $exception->limit());
            $this->assertSame(6, $exception->hits());
            $this->assertSame(0, $exception->remaining());
            $this->assertSame(2, $exception->resetsIn());

            sleep($exception->resetsIn());

            $quota = $factory->create($resource, $limit, $ttl)->hit();

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
    public function block_returns_quota_right_away_if_not_exceeded(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();

        $quota = $factory->create($resource, $limit, $ttl)->block(10);

        $this->assertSame($start, time());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function can_block_throttle_if_available_within_passed_time(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->create()->block(10);

        $this->assertSame($start + 2, time());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function can_block_throttle_if_available_at_exactly_passed_time(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->block(2);

        $this->assertSame($start + 2, time());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function block_throws_quota_exceeded_exception_right_away_if_not_going_to_be_available_within_passed_time(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 10;
        $factory = self::factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->hit();
        $factory->create($resource, $limit, $ttl)->hit();

        try {
            $factory->create($resource, $limit, $ttl)->block(2);
        } catch (QuotaExceeded $exception) {
            $this->assertSame($start, time());
            $this->assertSame(3, $exception->hits());
            $this->assertSame(0, $exception->remaining());
            $this->assertSame(10, $exception->resetsIn());

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_reset_throttle(): void
    {
        $throttle = self::factory()->create('foo', 5, 60);
        $throttle->reset();

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
