<?php

namespace Zenstruck\Governator\Exception;

use Zenstruck\Governator\Quota;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class QuotaExceeded extends \RuntimeException
{
    private Quota $quota;

    public function __construct(Quota $quota)
    {
        $this->quota = $quota;

        parent::__construct(\sprintf('Quota Exceeded (%d/%d), resets in %d seconds.', $quota->hits(), $quota->limit(), $quota->resetsIn()));
    }

    /**
     * @return Quota The exceeded Quota object for the throttle
     */
    public function quota(): Quota
    {
        return $this->quota;
    }

    /**
     * @return string The throttle's unique identifier
     */
    public function resource(): string
    {
        return $this->quota->resource();
    }

    /**
     * @return int The throttle's limit
     */
    public function limit(): int
    {
        return $this->quota->limit();
    }

    /**
     * @return int The number of throttle hits
     */
    public function hits(): int
    {
        return $this->quota->hits();
    }

    /**
     * @return int Number of allowed hits before the throttle resets (always 0)
     */
    public function remaining(): int
    {
        return 0;
    }

    /**
     * @return \DateTimeImmutable When the throttle resets (never in the past)
     */
    public function resetsAt(): \DateTimeImmutable
    {
        return $this->quota->resetsAt();
    }

    /**
     * @return int Number of seconds until the throttle resets (never negative)
     */
    public function resetsIn(): int
    {
        return $this->quota->resetsIn();
    }
}
