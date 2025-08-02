<?php

declare(strict_types=1);

namespace sentience\Models;

use sentience\Helpers\Arrays;
use src\Models\Attributes\Column;
use src\Models\Attributes\Table;
use src\Models\Exceptions\TableException;
use src\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public static function getTable(): string
    {
        $attributes = static::getClassAttributes(Table::class);

        if (Arrays::empty($attributes)) {
            throw new TableException('no table attribute specified on model %s', static::class);
        }

        return $attributes[0]->newInstance()->table;
    }

    public static function getColumn(string $property): string
    {
        $attributes = static::getPropertyAttributes($property, Column::class);

        if (Arrays::empty($attributes)) {
            return $property;
        }

        return $attributes[0]->newInstance()->column;
    }
}
