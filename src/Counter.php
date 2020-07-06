<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Counter
{
    private int $hits;
    private int $resetsAt;

    public function __construct(int $hits, int $resetsAt)
    {
        $this->hits = $hits;
        $this->resetsAt = $resetsAt;
    }

    public function hits(): int
    {
        return $this->hits;
    }

    public function resetsAt(): int
    {
        $currentTime = time();

        return $this->resetsAt < $currentTime ? $currentTime : $this->resetsAt;
    }

    public function resetsIn(): int
    {
        return \max(0, $this->resetsAt - time());
    }

    public function addHit(): self
    {
        $clone = clone $this;
        ++$clone->hits;

        return $clone;
    }
}
