<?php

declare(strict_types=1);

namespace Sentience\Helpers;

use Sentience\Exceptions\JsonException;

class Json
{
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string
    {
        $json = json_encode($value, $flags, $depth);

        static::checkError();

        return $json;
    }

    public static function decode(string $json, bool $associative = true, int $depth = 512, int $flags = 0): mixed
    {
        $value = json_decode($json, $associative, $depth, $flags);

        static::checkError();

        return $value;
    }

    protected static function checkError(): void
    {
        if (json_last_error() == JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg());
        }
    }
}
