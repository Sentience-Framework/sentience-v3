<?php

namespace Sentience\Helpers;

class Math
{
    public static function kmToLat(float $km): float
    {
        return $km / 110.574;
    }

    public static function kmToLong(float $km, float $lat): float
    {
        return $km / (111.320 * cos(deg2rad($lat)));
    }

    public static function between(int|float $number, int|float $min, int|float $max): bool
    {
        return $number >= $min && $number <= $max;
    }
}
