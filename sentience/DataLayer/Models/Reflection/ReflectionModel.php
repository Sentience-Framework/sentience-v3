<?php

namespace Sentience\DataLayer\Models\Reflection;

use ReflectionClass;
use ReflectionProperty;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Strings;
use Sentience\DataLayer\Models\Attributes\Relations\Relation;
use Sentience\DataLayer\Models\Attributes\Table\PrimaryKeys;
use Sentience\DataLayer\Models\Attributes\Table\Table;
use Sentience\DataLayer\Models\Attributes\Table\UniqueConstraint;

class ReflectionModel
{
    protected ReflectionClass $reflectionClass;

    public function __construct(protected string|object $model)
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

    public function getProperty(string $property): ReflectionModelProperty
    {
        return new ReflectionModelProperty(
            $this,
            new ReflectionProperty($this->model, $property)
        );
    }

    public function getProperties(?int $filter = null): array
    {
        return array_map(
            fn(ReflectionProperty $reflectionProperty): ReflectionModelProperty => new ReflectionModelProperty($this, $reflectionProperty),
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
        $properties = $this->getProperties();

        $columns = [];

        foreach ($properties as $property) {
            if (!$property->isColumn()) {
                continue;
            }

            $columns[] = $property->getColumn();
        }

        return $columns;
    }

    public function getPrimaryKeys(): array
    {
        $attributes = $this->reflectionClass->getAttributes(PrimaryKeys::class);

        return !Arrays::empty($attributes) ? $attributes[0]?->newInstance()->columns : null;
    }

    public function getUniqueConstraint(): ?UniqueConstraint
    {
        $attributes = $this->reflectionClass->getAttributes(UniqueConstraint::class);

        return !Arrays::empty($attributes) ? $attributes[0]?->newInstance() : null;
    }

    public function getRelation(string $property): ?Relation
    {
        $reflectionPropertyClass = new ReflectionModelProperty(
            $this,
            new ReflectionProperty($this->model, $property)
        );

        return $reflectionPropertyClass->getRelation();
    }
}
