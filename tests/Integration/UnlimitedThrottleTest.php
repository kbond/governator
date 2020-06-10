<?php

namespace Zenstruck\Governator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store\UnlimitedStore;
use Zenstruck\Governator\Tests\MocksClock;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnlimitedThrottleTest extends TestCase
{
    use MocksClock;

    /**
     * @test
     */
    public function always_allows_hit(): void
    {
        $throttle = (new ThrottleFactory(new UnlimitedStore()))->create('foo', 5, 60);

        $quota = $throttle->hit();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        $throttle->reset();
        $throttle->hit();
        $throttle->hit();
        $throttle->hit();
        $quota = $throttle->hit();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        sleep(4);

        $quota = $throttle->hit();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function never_blocks(): void
    {
        $throttle = (new ThrottleFactory(new UnlimitedStore()))->create('foo', 5, 60);
        $start = time();

        $quota = $throttle->block(5);

        $this->assertSame(time(), $start);
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }
}
