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
     * "Hits" the throttle, increasing its hit count by 1. If the throttle's quota is exceeded and
     * it resets in less than or equal to the passed time, block the process until the throttle is reset,
     * then "hit" it again.
     *
     * DOES NOT BLOCK the process if the throttle's quota is exceeded and its time until reset is greater
     * than the passed time.
     *
     * @param float $blockFor Max number of seconds to block the process waiting for the throttle to reset.
     *                        Partial seconds are rounded up to the next whole second.
     *
     * @return Quota Information on the current state of the throttle
     *
     * @throws QuotaExceeded If the current hit exceeds the throttle's limit and the passed time
     *                       is less then the throttle's "time to live"
     */
    public function hit(float $blockFor = 0.0): Quota
    {
        // TODO Remove if ever allow partial second blocking
        $blockFor = (float) \ceil($blockFor);

        try {
            return $this->hitStore();
        } catch (QuotaExceeded $exception) {
            if ($exception->resetsIn() > $blockFor) {
                throw $exception;
            }
        }

        sleep($exception->resetsIn());

        return $this->hitStore();
    }

    /**
     * Resets the throttle.
     */
    public function reset(): void
    {
        $this->store->reset($this->key);
    }

    private function hitStore(): Quota
    {
        $quota = new Quota($this->key, $this->store->hit($this->key));

        if ($quota->hasBeenExceeded()) {
            throw new QuotaExceeded($quota);
        }

        return $quota;
    }
}
