<?php

namespace Sentience\Database\Queries;

use ReflectionProperty;
use Sentience\Exceptions\ModelException;
use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\AutoIncrement;

class AlterModel extends ModelsQueryAbstract
{
    public function execute(): null
    {
        $model = $this->models[0];

        $this->validateModel($model);

        $table = $model::getTable();
        $columns = $model::getColumns();
        $primaryKeys = $model::getPrimaryKeys();
        $uniqueColumns = $model::getUniqueColumns();

        $columnsInDatabase = $this->database->select($table)
            ->limit(0)
            ->execute()
            ->getColumns();

        $columnsToAdd = [];
        $columnsToDrop = [];

        foreach ($columns as $column => $property) {
            if (in_array($column, $columnsInDatabase)) {
                continue;
            }

            $columnsToAdd[] = $column;
        }

        foreach ($columnsInDatabase as $column) {
            if (array_key_exists($column, $columns)) {
                continue;
            }

            $columnsToDrop[] = $column;
        }

        if ((count($columnsToAdd) + count($columnsToDrop)) == 0) {
            return null;
        }

        $query = $this->database->alterTable($table);

        foreach ($columnsToAdd as $column) {
            $property = $columns[$column];

            if (!Reflector::hasNamedType($model, $property)) {
                throw new ModelException('empty or union types are not allowed as model properties');
            }

            $reflectionProperty = new ReflectionProperty($model, $property);
            $reflectionType = $reflectionProperty->getType();

            $propertyType = Reflector::toNamedType($reflectionType);
            $propertyAllowsNull = $reflectionType->allowsNull();
            $propertyHasDefaultValue = $reflectionProperty->hasDefaultValue();
            $propertyDefaultValue = $reflectionProperty->getDefaultValue();
            $propertyIsPrimaryKey = in_array($property, array_values($primaryKeys));
            $propertyHasAutoIncrementAttribute = Reflector::propertyHasAttribute($model, $property, AutoIncrement::class);

            $columnType = $this->dialect->phpTypeToColumnType(
                $propertyType,
                $propertyHasAutoIncrementAttribute,
                $propertyIsPrimaryKey,
                in_array($property, array_values($uniqueColumns))
            );

            $query->addColumn(
                $column,
                $columnType,
                !$propertyAllowsNull,
                $propertyHasDefaultValue ? $propertyDefaultValue : null,
                $propertyHasAutoIncrementAttribute
            );

            if ($propertyIsPrimaryKey) {
                $query->addPrimaryKeys($column);
            }
        }

        foreach ($columnsToDrop as $column) {
            $query->dropColumn($column);
        }

        $query->execute();

        return null;
    }
}
