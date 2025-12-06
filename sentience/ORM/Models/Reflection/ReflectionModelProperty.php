<?php

namespace Sentience\ORM\Models\Reflection;

use ReflectionNamedType;
use ReflectionProperty;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Relations\Relation;
use Sentience\ORM\Models\Exceptions\MultipleTypesException;
use Sentience\ORM\Models\Model;

class ReflectionModelProperty
{
    public function __construct(protected ReflectionModel $reflectionModel, protected ReflectionProperty $reflectionProperty)
    {
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

    public function isColumn(): bool
    {
        return (bool) !$this->getRelation();
    }

    public function getColumn(): string
    {
        $columnAttribute = $this->getColumnAttribute();

        if (!$columnAttribute) {
            return Strings::toSnakeCase($this->reflectionProperty->getName());
        }

        return $columnAttribute->column;
    }

    public function getDefaultValue(): mixed
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

    public function getRelation(): ?Relation
    {
        $attributes = $this->reflectionProperty->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Relation) {
                return $instance;
            }
        }

        return null;
    }

    protected function getColumnAttribute(): ?Column
    {
        $attributes = $this->reflectionProperty->getAttributes(Column::class);

        return !Arrays::empty($attributes) ? $attributes[0]->newInstance() : null;
    }
}
