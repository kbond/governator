<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleBuilder
{
    private ThrottleFactory $factory;
    private string $resource;
    private ?int $limit = null;
    private ?int $ttl = null;

    public function __construct(ThrottleFactory $factory, string $resource)
    {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    public function allow(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function every(int $seconds): self
    {
        $this->ttl = $seconds;

        return $this;
    }

    public function create(): Throttle
    {
        if (null === $this->limit) {
            throw new \LogicException('A "limit" has not been set.'); // todo improve
        }

        if (null === $this->ttl) {
            throw new \LogicException('A "TTL" has not been set.'); // todo improve
        }

        return $this->factory->create($this->resource, $this->limit, $this->ttl);
    }

    public function hit(): Quota
    {
        return $this->create()->hit();
    }

    public function block(int $for): Quota
    {
        return $this->create()->block($for);
    }

    public function reset(): void
    {
        $this->create()->reset();
    }
}
