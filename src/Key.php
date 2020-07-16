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
    private string $prefix;
    private ?string $string = null;

    public function __construct(string $resource, int $limit, int $ttl, string $prefix = '')
    {
        if (empty($resource)) {
            throw new \InvalidArgumentException('A non-empty string is required for a throttle\'s "resource".');
        }

        if ($limit < 1) {
            throw new \InvalidArgumentException('A positive integer is required for a throttle\'s "limit".');
        }

        if ($ttl < 1) {
            throw new \InvalidArgumentException('A positive number is required for a throttle\'s "time to live".');
        }

        $this->resource = $resource;
        $this->limit = $limit;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    public function __toString(): string
    {
        return $this->string ?: $this->string = $this->prefix.\rtrim(\strtr(\base64_encode($this->resource), '+/', '-_'), '=').$this->limit.$this->ttl;
    }

    public function createCounter(): Counter
    {
        return new Counter(0, time() + $this->ttl);
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
