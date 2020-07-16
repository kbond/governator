<?php

namespace Zenstruck\Governator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Store
{
    public function hit(Key $key): Counter;

    public function status(Key $key): Counter;

    public function reset(Key $key): void;
}
