<?php

declare(strict_types=1);

namespace Sentience\Models;

use ReflectionClass;
use ReflectionProperty;
use Sentience\Database\Database;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\Columns\Column;
use Sentience\Models\Attributes\Table\PrimaryKeys;
use Sentience\Models\Attributes\Table\Table;
use Sentience\Models\Attributes\Table\UniqueConstraint;
use Sentience\Models\Exceptions\MultipleTypesException;
use Sentience\Models\Exceptions\TableException;
use Sentience\Timestamp\Timestamp;
use Sentience\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public function fromDatabase(array $assoc): static
    {
        $columns = static::getColumns();

        $dialect = Database::getInstance()->dialect;

        foreach ($assoc as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                continue;
            }

            $property = $columns[$key];

            if (!Reflector::hasNamedType($this, $property)) {
                throw new MultipleTypesException('models cannot have mixed or union types');
            }

            $type = Reflector::toNamedType((new ReflectionProperty($this, $property))->getType());

            $this->{$property} = match ($type) {
                'bool' => $dialect->parseBool($value),
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                Timestamp::class => $dialect->parseTimestamp($value),
                default => $value
            };
        }

        return $this;
    }

    public static function getTable(): string
    {
        $tableAttributes = static::getClassAttributes(Table::class);

        if (Arrays::empty($tableAttributes)) {
            throw new TableException('no table attribute specified on model %s', static::class);
        }

        return $tableAttributes[0]->newInstance()->table;
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

    public static function getColumn(string $property): ?string
    {
        if (!property_exists(static::class, $property)) {
            return null;
        }

        $columnAttributes = static::getPropertyAttributes($property, Column::class);

        if (Arrays::empty($columnAttributes)) {
            return $property;
        }

        return $columnAttributes[0]->newInstance()->column;
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

    public static function getUniqueColumns(): array
    {
        $uniqueConstraintAttributes = static::getClassAttributes(UniqueConstraint::class);

        if (Arrays::empty($uniqueConstraintAttributes)) {
            return [];
        }

        $properties = $uniqueConstraintAttributes[0]->newInstance()->properties;

        $columns = [];

        foreach ($properties as $property) {
            $column = static::getColumn($property);

            $columns[$column] = $property;
        }

        return array_filter($columns);
    }
}
