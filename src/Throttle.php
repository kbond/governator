<?php

namespace Zenstruck\Governator;

use Zenstruck\Governator\Exception\QuotaExceeded;

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

    /**
     * @throws QuotaExceeded
     */
    public function hit(): Quota
    {
        $quota = new Quota($this->limit, $this->store->hit($this->key()));

        if ($quota->hasBeenExceeded()) {
            throw new QuotaExceeded($quota);
        }

        return $quota;
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
