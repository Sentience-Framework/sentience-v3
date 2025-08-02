<?php

namespace sentience\Models;

use src\Models\Attributes\Column;
use src\Models\Attributes\Table;
use src\Models\Exceptions\NoTableException;
use src\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public static function getTable(): string
    {
        $attributes = static::getClassAttributes(Table::class);

        if (count($attributes) == 0) {
            throw new NoTableException('no table attribute specified on model %s', static::class);
        }

        return $attributes[0]->newInstance()->table;
    }

    public static function getColumn(string $property): string
    {
        $attributes = static::getPropertyAttributes($property, Column::class);

        if (count($attributes) == 0) {
            return $property;
        }

        return $attributes[0]->newInstance()->column;
    }
}
