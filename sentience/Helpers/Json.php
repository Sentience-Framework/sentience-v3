<?php

declare(strict_types=1);

namespace sentience\Helpers;

use sentience\Exceptions\JsonException;

class Json
{
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string
    {
        $json = json_encode($value, $flags, $depth);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }

        throw new JsonException(json_last_error_msg());
    }

    public static function decode(string $json, bool $associative = true, int $depth = 512, int $flags = 0): mixed
    {
        $value = json_decode($json, $associative, $depth, $flags);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $value;
        }

        throw new JsonException(json_last_error_msg());
    }
}
