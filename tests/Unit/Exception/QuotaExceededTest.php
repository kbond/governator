<?php

namespace Zenstruck\Governator\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Exception\QuotaExceeded;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Quota;

/**
 * @group time-sensitive
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class QuotaExceededTest extends TestCase
{
    /**
     * @test
     */
    public function sets_message(): void
    {
        $exception = new QuotaExceeded(new Quota(new Key('foo', 10, 60), new Counter(12, time() + 9)));

        $this->assertSame('Quota Exceeded (12/10), resets in 9 seconds.', $exception->getMessage());
    }
}
