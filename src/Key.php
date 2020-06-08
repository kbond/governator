<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Key
{
    private string $resource;
    private int $limit;
    private int $ttl;

    public function __construct(string $resource, int $limit, int $ttl)
    {
        $this->resource = $resource;
        $this->limit = $limit;
        $this->ttl = $ttl;
    }

    public function __toString(): string
    {
        return 'throttle_'.$this->resource.$this->limit.$this->ttl;
    }

    public function createCounter(): Counter
    {
        return new Counter(0, \time() + $this->ttl);
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }
}
