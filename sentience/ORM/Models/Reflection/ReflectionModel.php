<?php

namespace Sentience\ORM\Models\Reflection;

use ReflectionClass;
use ReflectionProperty;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Attributes\Table\UniqueConstraint;

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

    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getProperties(?int $filter = null): array
    {
        return array_map(
            fn (ReflectionProperty $reflectionProperty): ReflectionModelProperty => new ReflectionModelProperty($this, $reflectionProperty),
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

    public function getColumns(): array
    {
        return array_map(
            fn (ReflectionModelProperty $reflectionModelProperty): string => $reflectionModelProperty->getColumn(),
            $this->getProperties()
        );
    }

    public function getPrimaryKeys(): array
    {
        return $this->reflectionClass->getAttributes(PrimaryKeys::class)[0]?->newInstance()?->columns ?? [];
    }

    public function getUniqueConstraint(): ?UniqueConstraint
    {
        return $this->reflectionClass->getAttributes(UniqueConstraint::class)[0]?->newInstance() ?? null;
    }
}
