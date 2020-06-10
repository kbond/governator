<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleFactory
{
    private Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function create(string $resource, int $limit, int $ttl): Throttle
    {
        return new Throttle($this->store, new Key($resource, $limit, $ttl));
    }

    public function throttle(string $resource): ThrottleBuilder
    {
        return new ThrottleBuilder($this, $resource);
    }
}
