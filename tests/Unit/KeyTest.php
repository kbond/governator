<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Key;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KeyTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidNumberProvider
     */
    public function ttl_must_be_positive($number): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A positive number is required for a throttle\'s "time to live".');

        new Key('foo', 5, $number);
    }

    /**
     * @test
     * @dataProvider invalidNumberProvider
     */
    public function limit_must_be_positive($number): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A positive integer is required for a throttle\'s "limit".');

        new Key('foo', $number, 60);
    }

    /**
     * @test
     */
    public function resource_must_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-empty string is required for a throttle\'s "resource".');

        new Key('', 5, 60);
    }

    public static function invalidNumberProvider(): iterable
    {
        yield [0];
        yield [-1];
    }

    /**
     * @test
     */
    public function can_access_resource(): void
    {
        $this->assertSame('foo', (new Key('foo', 10, 60))->resource());
    }

    /**
     * @test
     */
    public function resource_does_not_include_prefix(): void
    {
        $this->assertSame('foo', (new Key('foo', 10, 60, 'my-prefix-'))->resource());
    }

    /**
     * @test
     */
    public function can_convert_to_string(): void
    {
        $this->assertSame('foo1060', (string) new Key('foo', 10, 60));
    }

    /**
     * @test
     */
    public function converting_to_string_includes_prefix(): void
    {
        $this->assertSame('my-prefix-foo1060', (string) new Key('foo', 10, 60, 'my-prefix-'));
    }
}
