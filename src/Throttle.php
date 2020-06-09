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

    public function __construct(Store $store, string $resource, int $limit, int $ttl)
    {
        $this->store = $store;
        $this->resource = $resource;
        $this->ttl = $ttl;
        $this->limit = $limit;
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

    /**
     * @throws QuotaExceeded
     */
    public function block(int $for): Quota
    {
        try {
            return $this->hit();
        } catch (QuotaExceeded $exception) {
            if ($exception->resetsIn() > $for) {
                throw $exception;
            }
        }

        sleep($exception->resetsIn());

        return $this->hit();
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
