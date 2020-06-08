<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RateLimit
{
    private int $limit;
    private int $hits;
    private \DateTimeImmutable $resetsAt;

    public function __construct(int $limit, int $hits, \DateTimeImmutable $resetsAt)
    {
        $this->limit = $limit;
        $this->hits = $hits;
        $this->resetsAt = $resetsAt;
    }

    public static function forKey(Key $key): self
    {
        return new self($key->limit(), 0, \DateTimeImmutable::createFromFormat('U', \time() + $key->ttl()));
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function hits(): int
    {
        return $this->hits;
    }

    public function remaining(): int
    {
        return \max(0, $this->limit - $this->hits);
    }

    public function resetsAt(): \DateTimeImmutable
    {
        return $this->resetsAt;
    }

    /**
     * @return int The seconds until reset
     */
    public function resetsIn(): int
    {
        return \max(0, $this->resetsAt->getTimestamp() - \time());
    }

    public function addHit(): self
    {
        $clone = clone $this;
        ++$clone->hits;

        return $clone;
    }

    /**
     * @internal
     */
    public function hasBeenExceeded(): bool
    {
        return $this->hits > $this->limit;
    }
}
