<?php

declare(strict_types=1);

namespace Modules\Models\Reflection;

use ReflectionNamedType;
use ReflectionProperty;
use Modules\Database\Database;
use Modules\Helpers\Arrays;
use Modules\Helpers\Strings;
use Modules\Models\Attributes\Columns\AutoIncrement;
use Modules\Models\Attributes\Columns\Column;
use Modules\Models\Exceptions\MultipleTypesException;
use Modules\Models\Model;

class ReflectionModelProperty
{
    protected ReflectionProperty $reflectionProperty;

    public function __construct(protected ReflectionModel $reflectionModel, string $property)
    {
        $this->reflectionProperty = new ReflectionProperty($reflectionModel->getClass(), $property);
    }

    public function isInitialized(Model $model): bool
    {
        return $this->reflectionProperty->isInitialized($model);
    }

    public function getProperty(): string
    {
        return $this->reflectionProperty->getName();
    }

    public function getValue(Model $model): string
    {
        return $this->reflectionProperty->getValue($model);
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
        return in_array(
            $this->getColumn(),
            $this->reflectionModel->getPrimaryKeys()
        );
    }

    public function isUnique(): bool
    {
        $uniqueConstraint = $this->reflectionModel->getUniqueConstraint();

        if (!$uniqueConstraint) {
            return false;
        }

        return in_array(
            $this->getColumn(),
            $uniqueConstraint->columns
        );
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
