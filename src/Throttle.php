<?php

namespace Zenstruck\Governator;

use Zenstruck\Governator\Exception\QuotaExceeded;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Throttle
{
    private Store $store;
    private Key $key;

    public function __construct(Store $store, Key $key)
    {
        $this->store = $store;
        $this->key = $key;
    }

    /**
     * @throws QuotaExceeded
     */
    public function hit(): Quota
    {
        $quota = new Quota($this->key->limit(), $this->store->hit($this->key));

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
        $this->store->reset($this->key);
    }
}
