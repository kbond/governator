<?php

namespace Zenstruck\Governator;

/**
 * Information on the current state of the throttle.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Quota
{
    private int $limit;
    private Counter $counter;

    public function __construct(int $limit, Counter $counter)
    {
        $this->limit = $limit;
        $this->counter = $counter;
    }

    /**
     * @return int The throttle's limit
     */
    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * @return int The number of throttle hits
     */
    public function hits(): int
    {
        return $this->counter->hits();
    }

    /**
     * @return int Number of allowed hits before the throttle resets (never negative)
     */
    public function remaining(): int
    {
        return \max(0, $this->limit - $this->hits());
    }

    /**
     * @return \DateTimeImmutable When the throttle resets (never in the past)
     */
    public function resetsAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', $this->counter->resetsAt());
    }

    /**
     * @return int Number of seconds until the throttle resets (never negative)
     */
    public function resetsIn(): int
    {
        return $this->counter->resetsIn();
    }

    public function hasBeenExceeded(): bool
    {
        return $this->counter->hits() > $this->limit;
    }
}
