<?php

declare(strict_types=1);

namespace Sentience\Models\Reflection;

use ReflectionNamedType;
use ReflectionProperty;
use Sentience\Database\Database;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\Models\Attributes\Columns\AutoIncrement;
use Sentience\Models\Attributes\Columns\Column;
use Sentience\Models\Exceptions\MultipleTypesException;

class ReflectionModelProperty
{
    protected ReflectionProperty $reflectionProperty;

    public function __construct(protected ReflectionModel $reflectionModel, string $property)
    {
        $this->reflectionProperty = new ReflectionProperty($reflectionModel->getClass(), $property);
    }

    public function getProperty(): string
    {
        return $this->reflectionProperty->getName();
    }

    public function getColumn(): string
    {
        $columnAttribute = $this->getColumnAttribute();

        if (!$columnAttribute) {
            return Strings::toSnakeCase($this->reflectionProperty->getName());
        }

        return $columnAttribute->column;
    }

    public function getColumnType(): string
    {
        $dialect = Database::getInstance()->dialect;

        return $dialect->phpTypeToColumnType(
            $this->getType(),
            $this->isAutoIncrement(),
            $this->isPrimaryKey(),
            $this->isUnique()
        );
    }

    public function getColumnDefault(): mixed
    {
        $columnAttribute = $this->getColumnAttribute();

        if (!$columnAttribute) {
            return $this->reflectionProperty->getDefaultValue();
        }

        return $columnAttribute->default;
    }

    public function getType(): string
    {
        $reflectionType = $this->reflectionProperty->getType();

        if ($reflectionType instanceof ReflectionNamedType) {
            return $reflectionType->getName();
        }

        throw new MultipleTypesException('models do not support union types');
    }

    public function allowsNull(): bool
    {
        return $this->reflectionProperty->getType()->allowsNull();
    }

    public function isPrimaryKey(): bool
    {
        $primaryKeys = $this->reflectionModel->getUniqueConstraints();

        $property = $this->getProperty();

        return in_array($property, $primaryKeys);
    }

    public function isUnique(): bool
    {
        $uniqueConstraints = $this->reflectionModel->getUniqueConstraints();

        $property = $this->getProperty();

        foreach ($uniqueConstraints as $uniqueConstraint) {
            if (in_array($property, $uniqueConstraint->properties)) {
                return true;
            }
        }

        return false;
    }

    public function isAutoIncrement(): bool
    {
        return !Arrays::empty($this->reflectionProperty->getAttributes(AutoIncrement::class));
    }

    protected function getColumnAttribute(): ?Column
    {
        return $this->reflectionProperty->getAttributes(Column::class)[0]?->newInstance() ?? null;
    }
}
