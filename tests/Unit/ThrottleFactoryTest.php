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
        $key = (new \ReflectionClass($throttle))->getProperty('key');
        $key->setAccessible(true);
        $key = $key->getValue($throttle);

        $this->assertSame('throttle_foo560', (string) $key);
    }

    /**
     * @test
     */
    public function for_with_default_prefix(): void
    {
        $throttle = ThrottleFactory::for('memory')->create('foo', 5, 60);
        $key = (new \ReflectionClass($throttle))->getProperty('key');
        $key->setAccessible(true);
        $key = $key->getValue($throttle);

        $this->assertSame('throttle_foo560', (string) $key);
    }

    /**
     * @test
     */
    public function construct_with_custom_prefix(): void
    {
        $throttle = (new ThrottleFactory(new MemoryStore(), 'my-prefix-'))->create('foo', 5, 60);
        $key = (new \ReflectionClass($throttle))->getProperty('key');
        $key->setAccessible(true);
        $key = $key->getValue($throttle);

        $this->assertSame('my-prefix-foo560', (string) $key);
    }

    /**
     * @test
     */
    public function for_with_custom_prefix(): void
    {
        $throttle = ThrottleFactory::for('memory', 'my-prefix-')->create('foo', 5, 60);
        $key = (new \ReflectionClass($throttle))->getProperty('key');
        $key->setAccessible(true);
        $key = $key->getValue($throttle);

        $this->assertSame('my-prefix-foo560', (string) $key);
    }
}
