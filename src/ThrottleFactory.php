<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleFactory
{
    private Store $store;
    private string $prefix;

    /**
     * @param string $prefix Global resource prefix for created throttles
     */
    public function __construct(Store $store, string $prefix = 'throttle_')
    {
        $this->store = $store;
        $this->prefix = $prefix;
    }

    /**
     * Create for passed connection DSN/object.
     *
     * @param string|object $connection
     * @param string        $prefix     Global resource prefix for created throttles
     */
    public static function for($connection, string $prefix = 'throttle_'): self
    {
        return new self(StoreFactory::create($connection), $prefix);
    }

    /**
     * Create a throttle.
     *
     * @param string $resource Unique identifier for the throttle
     * @param int    $limit    The maximum number of throttle "hits" in its "time window"
     * @param float  $ttl      The "time window" for the throttle in seconds.
     *                         Partial seconds are rounded up to the next whole second.
     */
    public function create(string $resource, int $limit, float $ttl): Throttle
    {
        return new Throttle($this->store, new Key($resource, $limit, $ttl, $this->prefix));
    }

    /**
     * Create a fluent interface throttle builder.
     *
     * @see ThrottleBuilder
     *
     * @param string $resource Unique identifier for the throttle
     */
    public function throttle(string $resource): ThrottleBuilder
    {
        return new ThrottleBuilder($this, $resource);
    }
}
