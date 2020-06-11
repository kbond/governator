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
     * "Hits" the throttle, increasing its hit count by 1.
     *
     * @return Quota Information on the current state of the throttle
     *
     * @throws QuotaExceeded If the current hit exceeds the throttle's limit
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
     * "Hits" the throttle, increasing its hit count by 1. If the throttle's quota is exceeded and
     * it resets in less than or equal to the passed time, block the process until the throttle is reset,
     * then "hit" it.
     *
     * DOES NOT BLOCK the process if the throttle's quota is exceeded and its time until reset is greater
     * than the passed time.
     *
     * @param float $for Max number of seconds to block the process waiting for the throttle to reset.
     *                   Partial seconds are rounded up to the next whole second.
     *
     * @throws QuotaExceeded If the current hit exceeds the throttle's limit and the passed number of
     *                       seconds is less then the throttle's "time to live"
     */
    public function block(float $for): Quota
    {
        // TODO Remove if ever allow partial second blocking
        $for = (float) \ceil($for);

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

    /**
     * Resets the throttle.
     */
    public function reset(): void
    {
        $this->store->reset($this->key);
    }
}
