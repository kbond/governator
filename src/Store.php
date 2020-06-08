<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Store
{
    public function hit(Key $key): RateLimit;

    public function reset(Key $key): void;
}
