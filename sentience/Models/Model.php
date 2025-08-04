<?php

declare(strict_types=1);

namespace sentience\Models;

use ReflectionClass;
use ReflectionProperty;
use sentience\Database\Database;
use sentience\Helpers\Arrays;
use sentience\Helpers\Reflector;
use sentience\Models\Attributes\Column;
use sentience\Models\Attributes\Table;
use sentience\Models\Exceptions\MultipleTypesException;
use sentience\Models\Exceptions\TableException;
use sentience\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public static function fromArray(array $associative): static
    {
        $model = new static();

        $columns = static::getColumns();

        $dialect = Database::getInstance()->dialect;

        foreach ($associative as $key => $value) {
            if (array_key_exists($key, $columns)) {
                continue;
            }

            $property = $columns[$key];

            if (!Reflector::hasSingularType(static::class, $property)) {
                throw new MultipleTypesException('models cannot have union types');
            }

            $type = (string) (new ReflectionProperty(static::class, $property))->getType();

            $model->{$property} = match ($type) {
                '?bool', 'bool' => $dialect->parseBool($value),
                '?int', 'int' => (int) $value,
                '?float', 'float' => (float) $value,
                '?string', 'string' => (string) $value,
                '?DateTime', 'DateTime' => $dialect->parseBool($value),
                default => $value
            };
        }

        return $model;
    }

    public static function fromObject(object $data): static
    {
        return static::fromArray((array) $data);
    }

    public static function getTable(): string
    {
        $attributes = static::getClassAttributes(Table::class);

        if (Arrays::empty($attributes)) {
            throw new TableException('no table attribute specified on model %s', static::class);
        }

        return $attributes[0]->newInstance()->table;
    }

    public static function getColumns(): array
    {
        $reflectionClass = new ReflectionClass(static::class);

        $reflectionProperties = $reflectionClass->getProperties();

        $properties = [];

        foreach ($reflectionProperties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $column = static::getColumn($property);

            $properties[$column] = $property;
        }

        return $properties;
    }

    public static function getColumn(string $property): ?string
    {
        if (!property_exists(static::class, $property)) {
            return null;
        }

        $attributes = static::getPropertyAttributes($property, Column::class);

        if (Arrays::empty($attributes)) {
            return $property;
        }

        return $attributes[0]->newInstance()->column;
    }
}
