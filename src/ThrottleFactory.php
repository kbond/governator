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

    public function create(string $resource, int $limit = 60, int $ttl = 60): Throttle
    {
        return new Throttle($this->store, $resource, $limit, $ttl);
    }
}
