<?php

declare(strict_types=1);

namespace Sentience\Models\Reflection;

use ReflectionClass;
use ReflectionProperty;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\Models\Attributes\Table\PrimaryKeys;
use Sentience\Models\Attributes\Table\Table;
use Sentience\Models\Attributes\Table\UniqueConstraint;
use Sentience\Models\Exceptions\TableException;

class ReflectionModel
{

    protected ReflectionClass $reflectionClass;

    public function __construct(string|object $model)
    {
        $this->reflectionClass = new ReflectionClass($model);
    }

    public function getClass(): string
    {
        return $this->reflectionClass->getName();
    }

    public function getProperties(?int $filter = null): array
    {
        return array_map(
            fn(ReflectionProperty $reflectionProperty): ReflectionModelProperty => new ReflectionModelProperty(
                $this,
                $reflectionProperty->getName()
            ),
            $this->reflectionClass->getProperties($filter)
        );
    }

    public function getTable(): string
    {
        $tableAttributes = $this->reflectionClass->getAttributes(Table::class);

        if (!Arrays::empty($tableAttributes)) {
            return $tableAttributes[0]->newInstance()->table;
        }

        $shortName = $this->reflectionClass->getShortName();

        $pluralShortName = Strings::pluralize($shortName);

        return Strings::toSnakeCase($pluralShortName);
    }

    public function getPrimaryKeys(): array
    {
        $primaryKeyAttributes = $this->reflectionClass->getAttributes(PrimaryKeys::class);

        if (Arrays::empty($primaryKeyAttributes)) {
            return [];
        }

        $properties = $this->getProperties();

        $primaryKeyProperties = $primaryKeyAttributes[0]->newInstance()->properties;

        return array_values(
            array_filter(
                $properties,
                fn(ReflectionModelProperty $reflectionModelProperty): bool => in_array($reflectionModelProperty->getColumn(), $primaryKeyProperties)
            )
        );
    }

    public function getUniqueConstraints(): array
    {
        return $this->reflectionClass->getAttributes(UniqueConstraint::class);
    }
}
