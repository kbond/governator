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
    private string $resource;
    private ?int $limit = null;
    private ?float $ttl = null;

    public function __construct(ThrottleFactory $factory, string $resource)
    {
        $this->factory = $factory;
        $this->resource = $resource;
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
     * @param float $seconds The "time window" for the throttle in seconds.
     *                       Partial seconds are rounded up to the next whole second.
     */
    public function every(float $seconds): self
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

        return $this->factory->create($this->resource, $this->limit, $this->ttl);
    }

    /**
     * Create the throttle for the current configuration and "hit" it.
     *
     * @see Throttle::hit()
     */
    public function hit(): Quota
    {
        return $this->create()->hit();
    }

    /**
     * Create the throttle for the current configuration and "hit" it, potentially blocking the process
     * for the passed time if it's quota has been exceeded.
     *
     * @see Throttle::block()
     */
    public function block(float $for): Quota
    {
        return $this->create()->block($for);
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
