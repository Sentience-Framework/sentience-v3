<?php

namespace Sentience\Models;

use Sentience\Models\Reflection\ReflectionModel;
use Sentience\Models\Reflection\ReflectionModelProperty;
use Sentience\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public static function getTable(): string
    {
        return (new ReflectionModel(static::class))->getTable();
    }

    public static function getColumns(): array
    {
        return array_map(
            fn(ReflectionModelProperty $reflectionModelProperty): string => $reflectionModelProperty->getColumn(),
            (new ReflectionModel(static::class))->getProperties()
        );
    }
}
