<?php

namespace Sentience\ORM\Models\Attributes\Columns;

use Attribute;
use Sentience\Helpers\Json as JsonHelper;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Json
{
    public static function encode(mixed $value): string
    {
        return JsonHelper::encode($value);
    }

    public static function decodeToArray(string $json): array
    {
        return JsonHelper::decode($json, true);
    }

    public static function decodeToObject(string $json): object
    {
        return JsonHelper::decode($json, false);
    }
}
