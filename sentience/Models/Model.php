<?php

declare(strict_types=1);

namespace Sentience\Models;

use ReflectionClass;
use ReflectionProperty;
use Sentience\Database\Database;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\Column;
use Sentience\Models\Attributes\PrimaryKeys;
use Sentience\Models\Attributes\Table;
use Sentience\Models\Attributes\UniqueConstraint;
use Sentience\Models\Exceptions\MultipleTypesException;
use Sentience\Models\Exceptions\TableException;
use Sentience\Models\Traits\IsJsonSerializable;
use Sentience\Traits\HasAttributes;

class Model
{
    use IsJsonSerializable;
    use HasAttributes;

    public function fromArray(array $assoc): static
    {
        $columns = static::getColumns();

        $dialect = Database::getInstance()->dialect;

        foreach ($assoc as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                continue;
            }

            $property = $columns[$key];

            if (!Reflector::IsNamedType($this, $property)) {
                throw new MultipleTypesException('models cannot have mixed or union types');
            }

            $type = (string) (new ReflectionProperty($this, $property))->getType();

            $this->{$property} = match ($type) {
                '?bool', 'bool' => $dialect->parseBool($value),
                '?int', 'int' => (int) $value,
                '?float', 'float' => (float) $value,
                '?string', 'string' => (string) $value,
                '?DateTime', 'DateTime' => $dialect->parseDateTime($value),
                default => $value
            };
        }

        return $this;
    }

    public function fromObject(object $data): static
    {
        return $this->fromArray((array) $data);
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

        $columns = [];

        foreach ($reflectionProperties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $column = static::getColumn($property);

            $columns[$column] = $property;
        }

        return $columns;
    }

    public static function getUniqueColumns(): array
    {
        $attributes = static::getClassAttributes(UniqueConstraint::class);

        if (Arrays::empty($attributes)) {
            return [];
        }

        $properties = $attributes[0]->newInstance()->properties;

        $columns = [];

        foreach ($properties as $property) {
            $column = static::getColumn($property);

            $columns[$column] = $property;
        }

        return array_filter($columns);
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

    public static function getPrimaryKeys(): array
    {
        $primaryKeysAttribute = static::getClassAttributes(PrimaryKeys::class);

        if (Arrays::empty($primaryKeysAttribute)) {
            return [];
        }

        $columns = static::getColumns();

        $primaryKeysAttributeInstance = $primaryKeysAttribute[0]->newInstance();

        return array_filter(
            $columns,
            fn (string $property) => in_array($property, $primaryKeysAttributeInstance->properties)
        );
    }
}
