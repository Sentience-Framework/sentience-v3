<?php

declare(strict_types=1);
use Sentience\Helpers\Strings;

include 'vendor/autoload.php';


$array = [
    (new stdClass())
];

echo $array[1]?->name ?? null;

