<?php

declare(strict_types=1);

namespace Modules\Models;

use Modules\Models\Reflection\ReflectionModel;
use Modules\Models\Reflection\ReflectionModelProperty;
use Modules\Traits\HasAttributes;

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
            fn (ReflectionModelProperty $reflectionModelProperty): string => $reflectionModelProperty->getColumn(),
            (new ReflectionModel(static::class))->getProperties()
        );
    }
}
