<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Throttle
{
    private Store $store;
    private string $resource;
    private int $ttl;
    private int $limit;

    public function __construct(Store $store, string $resource, int $limit = 60, int $ttl = 60)
    {
        $this->store = $store;
        $this->resource = $resource;
        $this->ttl = $ttl;
        $this->limit = $limit;
    }

    public function allow(int $limit): self
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    public function every(int $seconds): self
    {
        $clone = clone $this;
        $clone->ttl = $seconds;

        return $clone;
    }

    public function hit(): RateLimit
    {
        $rateLimit = $this->store->hit($this->key());

        if ($rateLimit->hasBeenExceeded()) {
            throw new RateLimitExceeded($rateLimit);
        }

        return $rateLimit;
    }

    public function reset(): void
    {
        $this->store->reset($this->key());
    }

    private function key(): Key
    {
        return new Key($this->resource, $this->limit, $this->ttl);
    }
}
