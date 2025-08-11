<?php

namespace Sentience\Models\Reflection;

use ReflectionClass;
use Sentience\Helpers\Arrays;
use Sentience\Models\Attributes\Table\Table;
use Sentience\Models\Exceptions\TableException;

class ReflectionModel
{
    protected ReflectionClass $reflectionClass;

    public function __construct(string|object $model)
    {
        $this->reflectionClass = new ReflectionClass($model);
    }

    public function getTable(): string
    {
        $tableAttributes = $this->reflectionClass->getAttributes(Table::class);

        if (Arrays::empty($tableAttributes)) {
            throw new TableException('no table attribute specified on model %s', static::class);
        }

        return $tableAttributes[0]->newInstance()->table;
    }

    public function getPrimaryKeys(): array
    {
        $primaryKeysAttribute = static::getClassAttributes(PrimaryKeys::class);

        if (Arrays::empty($primaryKeysAttribute)) {
            return [];
        }

        $columns = static::getColumns();

        $primaryKeysAttributeInstance = $primaryKeysAttribute[0]->newInstance();

        return array_filter(
            $columns,
            fn(string $property) => in_array($property, $primaryKeysAttributeInstance->properties)
        );
    }
}
