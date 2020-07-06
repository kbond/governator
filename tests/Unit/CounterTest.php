<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Counter;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CounterTest extends TestCase
{
    /**
     * @test
     */
    public function resets_in_cannot_be_negative(): void
    {
        $this->assertSame(0, (new Counter(10, time() - 10))->resetsIn());
    }

    /**
     * @test
     */
    public function resets_at_cannot_be_in_the_past(): void
    {
        $this->assertSame(time(), (new Counter(10, time() - 10))->resetsAt());
    }

    /**
     * @test
     */
    public function can_add_hit(): void
    {
        $counter = new Counter(10, time());

        $this->assertSame(10, $counter->hits());
        $this->assertSame(11, $counter->addHit()->hits());
    }

    /**
     * @test
     */
    public function add_hit_is_immutable(): void
    {
        $counter = new Counter(10, 3);
        $newCounter = $counter->addHit();

        $this->assertNotSame(\spl_object_id($counter), \spl_object_id($newCounter));
    }
}
