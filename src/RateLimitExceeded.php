<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RateLimitExceeded extends \RuntimeException
{
    private RateLimit $rateLimit;

    public function __construct(RateLimit $rateLimit)
    {
        $this->rateLimit = $rateLimit;

        parent::__construct(); // todo - set message
    }

    public function limit(): int
    {
        return $this->rateLimit->limit();
    }

    public function hits(): int
    {
        return $this->rateLimit->hits();
    }

    public function remaining(): int
    {
        return $this->rateLimit->remaining();
    }

    public function resetsAt(): \DateTimeImmutable
    {
        return $this->rateLimit->resetsAt();
    }

    /**
     * @return int The seconds until reset
     */
    public function resetsIn(): int
    {
        return $this->rateLimit->resetsIn();
    }
}
