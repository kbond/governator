<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\Throttle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleTest extends TestCase
{
    /**
     * @test
     */
    public function negative_block_has_same_effect_as_zero(): void
    {
        $throttle = new Throttle(new MemoryStore(), new Key('foo', 1, 10));

        $throttle->acquire();

        $start = time();

        try {
            $throttle->acquire(-20);
        } catch (QuotaExceeded $e) {
            $this->assertSame(time(), $start);

            return;
        }

        $this->fail('Exception not thrown.');
    }
}
