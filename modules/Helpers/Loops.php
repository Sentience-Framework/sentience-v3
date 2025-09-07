<?php

namespace Modules\Helpers;

use Traversable;

class Loops
{
    public static function foreachWithIteration(array|Traversable $iterable, callable $callback): void
    {
        $iteration = 0;

        foreach ($iterable as $key => $value) {
            $break = $callback($value, $key, $iteration);

            if ($break) {
                break;
            }

            $iteration++;
        }
    }

    public static function whileTrueWithIteration(callable $callback): void
    {
        $iteration = 0;

        while (true) {
            $break = $callback($iteration);

            if ($break) {
                break;
            }

            $iteration++;
        }
    }
}
