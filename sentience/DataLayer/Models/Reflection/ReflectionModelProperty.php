<?php

namespace Sentience\DataLayer\Models\Reflection;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\DataLayer\Database\Enums\MySQLColumnEnum;
use Sentience\DataLayer\Database\Enums\PgSQLColumnEnum;
use Sentience\DataLayer\Database\Enums\SQLite3ColumnEnum;
use Sentience\DataLayer\Models\Attributes\Columns\AutoIncrement;
use Sentience\DataLayer\Models\Attributes\Columns\Column;
use Sentience\DataLayer\Models\Attributes\Relations\Relation;
use Sentience\DataLayer\Models\Exceptions\MultipleTypesException;
use Sentience\DataLayer\Models\Model;
use Sentience\Timestamp\Timestamp;

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

    public function getColumnType(DialectInterface $dialect): string
    {
        $type = $this->getType();

        if (is_subclass_of($type, BackedEnum::class)) {
            $type = (new ReflectionEnum($type))->getBackingType();
        }

        return match (true) {
            $dialect instanceof MySQLDialect => MySQLColumnEnum::getType(
                $type,
                $this->isPrimaryKey(),
                $this->isUnique()
            )->value,
            $dialect instanceof PgSQLDialect => PgSQLColumnEnum::getType(
                $type,
                $this->isAutoIncrement()
            )->value,
            $dialect instanceof SQLiteDialect => SQLite3ColumnEnum::getType(
                $type
            )->value,
            default => match ($type) {
                    'bool' => 'INT',
                    'int' => 'INT',
                    'float' => 'FLOAT',
                    'string' => 'TEXT',
                    Timestamp::class,
                    DateTime::class,
                    DateTimeInterface::class => 'DATETIME',
                    default => 'TEXT'
                }
        };
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
