<?php

// function version(int|string $version, array $lengths = [10000, 100, 1]): int
// {
//     if (is_int($version)) {
//         return $version;
//     }

//     $parts = explode(
//         '.',
//         strtok(
//             $version,
//             '-'
//         )
//     );

//     $partsCount = count($parts);

//     $number = 0;

//     foreach ($parts as $index => $part) {
//         $number += (int) $part * 100 ** ($partsCount - $index - 1);
//     }

//     return $number;
// }

// echo version('18.0');

echo SQLite3::escapeString('Hoi dit \'"` is een test--');
