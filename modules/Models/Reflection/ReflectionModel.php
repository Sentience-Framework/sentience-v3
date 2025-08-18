<?php

declare(strict_types=1);

namespace Modules\Models\Reflection;

use ReflectionClass;
use ReflectionProperty;
use Modules\Helpers\Arrays;
use Modules\Helpers\Strings;
use Modules\Models\Attributes\Table\PrimaryKeys;
use Modules\Models\Attributes\Table\Table;
use Modules\Models\Attributes\Table\UniqueConstraint;

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
            fn (ReflectionProperty $reflectionProperty): ReflectionModelProperty => new ReflectionModelProperty(
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
        return $this->reflectionClass->getAttributes(PrimaryKeys::class)[0]?->newInstance()?->columns ?? [];
    }

    public function getUniqueConstraint(): ?UniqueConstraint
    {
        return $this->reflectionClass->getAttributes(UniqueConstraint::class)[0]?->newInstance() ?? null;
    }
}
