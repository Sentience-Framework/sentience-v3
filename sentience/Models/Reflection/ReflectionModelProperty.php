<?php

namespace Sentience\Models\Reflection;

use BackedEnum;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\Models\Attributes\Columns\AutoIncrement;
use Sentience\Models\Attributes\Columns\Column;
use Sentience\Models\Enums\MySQLColumnEnum;
use Sentience\Models\Enums\PgSQLColumnEnum;
use Sentience\Models\Enums\SQLite3ColumnEnum;
use Sentience\Models\Exceptions\MultipleTypesException;
use Sentience\Models\Exceptions\UnknownDialectException;
use Sentience\Models\Model;

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
            default => throw new UnknownDialectException('unknown dialect %s', $dialect::class)
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

    protected function getColumnAttribute(): ?Column
    {
        return $this->reflectionProperty->getAttributes(Column::class)[0]?->newInstance() ?? null;
    }
}
