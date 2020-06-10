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
            throw new \LogicException(\sprintf('You must set a "Limit" for the throttle via "%s::allow($limit)"', self::class)); // todo improve
        }

        if (null === $this->ttl) {
            throw new \LogicException(\sprintf('You must set a "TTL" for the throttle via "%s::every($ttl)"', self::class));
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
