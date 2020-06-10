<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleFactory
{
    private Store $store;
    private string $prefix;

    public function __construct(Store $store, string $prefix = 'throttle_')
    {
        $this->store = $store;
        $this->prefix = $prefix;
    }

    /**
     * @param string|object $connection
     */
    public static function for($connection, string $prefix = 'throttle_'): self
    {
        return new self(StoreFactory::create($connection), $prefix);
    }

    public function create(string $resource, int $limit, float $ttl): Throttle
    {
        return new Throttle($this->store, new Key($resource, $limit, $ttl, $this->prefix));
    }

    public function throttle(string $resource): ThrottleBuilder
    {
        return new ThrottleBuilder($this, $resource);
    }
}
