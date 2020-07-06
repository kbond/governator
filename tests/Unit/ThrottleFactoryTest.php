<?php

namespace Zenstruck\Governator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Governator\Store\MemoryStore;
use Zenstruck\Governator\ThrottleFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function construct_with_default_prefix(): void
    {
        $throttle = (new ThrottleFactory(new MemoryStore()))->create('foo', 5, 60);
        $resource = \rtrim(\strtr(\base64_encode('foo'), '+/', '-_'), '=');

        $this->assertSame("throttle_{$resource}560", (string) $throttle->hit()->key());
    }

    /**
     * @test
     */
    public function for_with_default_prefix(): void
    {
        $throttle = ThrottleFactory::for('memory')->create('foo', 5, 60);
        $resource = \rtrim(\strtr(\base64_encode('foo'), '+/', '-_'), '=');

        $this->assertSame("throttle_{$resource}560", (string) $throttle->hit()->key());
    }

    /**
     * @test
     */
    public function construct_with_custom_prefix(): void
    {
        $throttle = (new ThrottleFactory(new MemoryStore(), 'my-prefix-'))->create('foo', 5, 60);
        $resource = \rtrim(\strtr(\base64_encode('foo'), '+/', '-_'), '=');

        $this->assertSame("my-prefix-{$resource}560", (string) $throttle->hit()->key());
    }

    /**
     * @test
     */
    public function for_with_custom_prefix(): void
    {
        $throttle = ThrottleFactory::for('memory', 'my-prefix-')->create('foo', 5, 60);
        $resource = \rtrim(\strtr(\base64_encode('foo'), '+/', '-_'), '=');

        $this->assertSame("my-prefix-{$resource}560", (string) $throttle->hit()->key());
    }
}
