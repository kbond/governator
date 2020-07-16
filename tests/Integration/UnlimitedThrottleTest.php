<?php

namespace Zenstruck\Governator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store\UnlimitedStore;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnlimitedThrottleTest extends TestCase
{
    /**
     * @test
     */
    public function always_allows_hit(): void
    {
        $throttle = (new ThrottleFactory(new UnlimitedStore()))->create('foo', 5, 60);

        $quota = $throttle->acquire();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        $throttle->reset();
        $throttle->acquire();
        $throttle->acquire();
        $throttle->acquire();
        $quota = $throttle->acquire();

        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());

        sleep(4);

        $quota = $throttle->acquire();

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

        $quota = $throttle->acquire(5);

        $this->assertSame(time(), $start);
        $this->assertSame(1, $quota->hits());
        $this->assertSame(4, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }

    /**
     * @test
     */
    public function status_always_returns_empty_quota(): void
    {
        $throttle = (new ThrottleFactory(new UnlimitedStore()))->create('foo', 5, 60);

        $throttle->acquire();
        $throttle->acquire();
        $throttle->acquire();
        $throttle->acquire();

        $quota = $throttle->status();

        $this->assertSame(0, $quota->hits());
        $this->assertSame(5, $quota->remaining());
        $this->assertSame(60, $quota->resetsIn());
    }
}
