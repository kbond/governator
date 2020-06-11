<?php

namespace Zenstruck\Governator;

/**
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

    public function limit(): int
    {
        return $this->limit;
    }

    public function hits(): int
    {
        return $this->counter->hits();
    }

    public function remaining(): int
    {
        return \max(0, $this->limit - $this->hits());
    }

    public function resetsAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', $this->counter->resetsAt());
    }

    public function resetsIn(): int
    {
        return $this->counter->resetsIn();
    }

    public function hasBeenExceeded(): bool
    {
        return $this->counter->hits() > $this->limit;
    }
}
