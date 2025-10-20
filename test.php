<?php

function version(int|string $version, array $lengths = [10000, 100, 1]): int
{
    if (is_int($version)) {
        return $version;
    }

    $parts = explode(
        '.',
        strtok(
            $version,
            '-'
        )
    );

    if (count($parts) > count($lengths)) {
        throw new Exception('not enough lengths defined to parse server version');
    }

    $number = 0;

    foreach ($parts as $index => $part) {
        $number += (int) $part * $lengths[$index];
    }

    return $number;
}

echo version('8.0.0');
