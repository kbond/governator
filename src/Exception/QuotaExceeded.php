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

        parent::__construct(); // todo - set message
    }

    public function quota(): Quota
    {
        return $this->quota;
    }

    public function limit(): int
    {
        return $this->quota->limit();
    }

    public function hits(): int
    {
        return $this->quota->hits();
    }

    public function remaining(): int
    {
        return $this->quota->remaining();
    }

    public function resetsAt(): \DateTimeImmutable
    {
        return $this->quota->resetsAt();
    }

    /**
     * @return int The seconds until reset
     */
    public function resetsIn(): int
    {
        return $this->quota->resetsIn();
    }
}
