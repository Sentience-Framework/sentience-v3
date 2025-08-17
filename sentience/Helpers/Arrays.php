<?php

declare(strict_types=1);

namespace Sentience\Helpers;

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

                continue;
            }

            $flattened[] = $value;
        }

        return $flattened;
    }

    public static function wrap(mixed $value): array
    {
        return !is_array($value) ? [$value] : $value;
    }

    public static function avg(array $array): float
    {
        return array_sum($array) / count($array);
    }

    public static function unique(array $array): array
    {
        return array_values(array_unique($array));
    }
}
