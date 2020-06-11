<?php

namespace Zenstruck\Governator\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Quota;
use Zenstruck\Governator\Tests\MocksClock;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class QuotaExceededTest extends TestCase
{
    use MocksClock;

    /**
     * @test
     */
    public function sets_message(): void
    {
        $exception = new QuotaExceeded(new Quota(10, new Counter(12, time() + 9)));

        $this->assertSame('Quota Exceeded (12/10), resets in 9 seconds.', $exception->getMessage());
    }
}
