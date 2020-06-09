<?php

namespace Zenstruck\Governator\Tests;

use Symfony\Bridge\PhpUnit\ClockMock;
use Zenstruck\Governator\Counter;
use Zenstruck\Governator\Key;
use Zenstruck\Governator\Throttle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait MocksClock
{
    /**
     * @beforeClass
     */
    public static function setUpClockMock(): void
    {
        ClockMock::register(static::class);

        ClockMock::register(Key::class);
        ClockMock::register(Counter::class);
        ClockMock::register(Throttle::class);

        foreach (static::clockMockClasses() as $class) {
            ClockMock::register($class);
        }

        ClockMock::withClockMock(true);
    }

    /**
     * @afterClass
     */
    public static function tearDownClockMock(): void
    {
        ClockMock::withClockMock(true);
    }

    /**
     * @return string[]
     */
    protected static function clockMockClasses(): iterable
    {
        return [];
    }
}
