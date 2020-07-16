<?php

namespace Zenstruck\Governator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Store;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BaseThrottleTest extends TestCase
{
    /**
     * @test
     */
    public function can_acquire_throttle(): void
    {
        $resource = 'foo';
        $limit = 5;
        $ttl = 60;
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $quota = $factory->create($resource, $limit, $ttl)->acquire();

        $this->assertSame('foo', $quota->resource());
        $this->assertSame(5, $quota->limit());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
        $this->assertSame(time() + 60, $quota->resetsAt()->getTimestamp());

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->acquire();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(2, $quota->hits());
        $this->assertSame(3, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        sleep(2);

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->acquire();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(3, $quota->hits());
        $this->assertSame(2, $quota->remaining());
        $this->assertSame(58, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function ensure_resets_after_ttl(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $quota = $factory->create($resource, $limit, $ttl)->acquire();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        $quota = $factory->create($resource, $limit, $ttl)->acquire();

        $this->assertSame(2, $quota->hits());
        $this->assertSame(0, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());

        sleep($quota->resetsIn());

        $quota = $factory->create($resource, $limit, $ttl)->acquire();
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
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();

        try {
            $factory->create($resource, $limit, $ttl)->acquire();
        } catch (QuotaExceeded $exception) {
            $this->assertSame('Quota Exceeded (6/5), resets in 2 seconds.', $exception->getMessage());
            $this->assertSame('foo', $exception->resource());
            $this->assertSame(5, $exception->limit());
            $this->assertSame(6, $exception->hits());
            $this->assertSame(0, $exception->remaining());
            $this->assertSame(2, $exception->resetsIn());
            $this->assertSame(time() + 2, $exception->resetsAt()->getTimestamp());
            $this->assertSame('foo', $exception->quota()->resource());
            $this->assertSame(5, $exception->quota()->limit());
            $this->assertSame(6, $exception->quota()->hits());
            $this->assertSame(0, $exception->quota()->remaining());
            $this->assertSame(2, $exception->quota()->resetsIn());
            $this->assertSame(time() + 2, $exception->quota()->resetsAt()->getTimestamp());

            sleep($exception->resetsIn());

            $quota = $factory->create($resource, $limit, $ttl)->acquire();

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
    public function acquire_with_block_returns_quota_right_away_if_not_exceeded(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 2;
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();

        $quota = $factory->create($resource, $limit, $ttl)->acquire(10);

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
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->acquire(10);

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
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();

        $quota = $factory->throttle($resource)->allow($limit)->every($ttl)->acquire(2);

        $this->assertSame($start + 2, time());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(1, $quota->remaining());
        $this->assertSame(2, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function acquire_with_block_throws_quota_exceeded_exception_right_away_if_not_going_to_be_available_within_passed_time(): void
    {
        $resource = 'foo';
        $limit = 2;
        $ttl = 10;
        $factory = $this->factory();
        $factory->create($resource, $limit, $ttl)->reset();

        $start = time();
        $factory->create($resource, $limit, $ttl)->acquire();
        $factory->create($resource, $limit, $ttl)->acquire();

        try {
            $factory->create($resource, $limit, $ttl)->acquire(2);
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
        $factory = $this->factory();
        $throttle = $factory->create('foo', 5, 60);
        $throttle->reset();

        $this->assertSame(4, $throttle->acquire()->remaining());
        $this->assertSame(3, $throttle->acquire()->remaining());

        $throttle->reset();

        $this->assertSame(4, $throttle->acquire()->remaining());
        $this->assertSame(3, $throttle->acquire()->remaining());

        $factory->throttle('foo')->allow(5)->every(60)->reset();

        $this->assertSame(4, $throttle->acquire()->remaining());
        $this->assertSame(3, $throttle->acquire()->remaining());
    }

    /**
     * @test
     */
    public function can_get_status_of_throttle_that_has_not_been_hit(): void
    {
        $resource = 'foo';
        $limit = 5;
        $ttl = 60;
        $throttle = $this->factory()->create($resource, $limit, $ttl);
        $throttle->reset();

        $quota = $throttle->status();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(0, $quota->hits());
        $this->assertSame(5, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function can_get_status_of_throttle_that_has_been_hit(): void
    {
        $resource = 'foo';
        $limit = 5;
        $ttl = 60;
        $throttle = $this->factory()->create($resource, $limit, $ttl);
        $throttle->reset();

        $throttle->acquire();

        $quota = $throttle->status();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        sleep(2);

        $quota = $throttle->status();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(58, $quota->resetsIn());

        $throttle->acquire();

        $quota = $throttle->status();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(2, $quota->hits());
        $this->assertSame(3, $quota->remaining());
        $this->assertSame(58, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function can_check_throttle_status_if_exceeded(): void
    {
        $resource = 'foo';
        $limit = 5;
        $ttl = 60;
        $throttle = $this->factory()->create($resource, $limit, $ttl);
        $throttle->reset();

        $throttle->hit();
        $throttle->hit();
        $throttle->hit();
        $throttle->hit();
        $throttle->hit();
        $throttle->hit();

        $quota = $throttle->status();

        $this->assertSame(5, $quota->limit());
        $this->assertSame(6, $quota->hits());
        $this->assertSame(0, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
        $this->assertTrue($quota->hasBeenExceeded());
    }

    abstract protected function createStore(): Store;

    private function factory(): ThrottleFactory
    {
        return new ThrottleFactory($this->createStore());
    }
}
