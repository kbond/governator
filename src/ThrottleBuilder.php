<?php

namespace Zenstruck\Governator;

/**
 * Fluent interface for configuring and creating throttles.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ThrottleBuilder
{
    private ThrottleFactory $factory;
    private array $resource;
    private ?int $limit = null;
    private ?int $ttl = null;

    public function __construct(ThrottleFactory $factory, string ...$resource)
    {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    public function with(string ...$resource): self
    {
        $this->resource = \array_merge($this->resource, $resource);

        return $this;
    }

    /**
     * @param int $limit The maximum number of throttle "hits" in its "time window"
     */
    public function allow(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $seconds the "time window" for the throttle in seconds
     */
    public function every(int $seconds): self
    {
        $this->ttl = $seconds;

        return $this;
    }

    /**
     * Create the throttle for the current configuration.
     *
     * @throws \LogicException If the limit or TTL was not set
     */
    public function create(): Throttle
    {
        if (null === $this->limit) {
            throw new \LogicException(\sprintf('You must set a "Limit" for the throttle via "%s::allow($limit)"', self::class));
        }

        if (null === $this->ttl) {
            throw new \LogicException(\sprintf('You must set a "TTL" for the throttle via "%s::every($ttl)"', self::class));
        }

        if (empty($this->resource)) {
            throw new \LogicException('The resource for the throttle cannot be blank.');
        }

        return $this->factory->create(\implode('', $this->resource), $this->limit, $this->ttl);
    }

    /**
     * Create the throttle for the current configuration and "hit" it, potentially blocking the process
     * for the passed time if it's quota has been exceeded.
     *
     * @see Throttle::hit()
     */
    public function hit(int $blockFor = 0): Quota
    {
        return $this->create()->hit($blockFor);
    }

    /**
     * Create the throttle for the current configuration and resets it.
     *
     * @see Throttle::reset()
     */
    public function reset(): void
    {
        $this->create()->reset();
    }
}
