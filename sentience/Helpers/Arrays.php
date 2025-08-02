<?php

declare(strict_types=1);

namespace sentience\Helpers;

class Arrays
{
    public static function empty(array $array): bool
    {
        return count($array) == 0;
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $flattened = [];

        foreach ($array as $value) {
            if ($depth > 0 && is_array($value)) {
                $flattened = array_merge($flattened, static::flatten($value, $depth - 1));
            }

            $flattened[] = $value;
        }

        return $flattened;
    }
}
