<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Store
{
    public function hit(Key $key): Quota;

    public function reset(Key $key): void;
}
