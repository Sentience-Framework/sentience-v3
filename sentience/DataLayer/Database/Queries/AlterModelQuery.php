<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\DataLayer\Models\Reflection\ReflectionModel;

class AlterModelQuery extends ModelsQueryAbstract
{
    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(bool $emulatePrepare = false): null
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);
        $reflectionModelProperties = $reflectionModel->getProperties();

        $table = $reflectionModel->getTable();

        $query = $this->database->alterTable($table);

        $columns = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $column = $reflectionModelProperty->getColumn();

            $columns[$column] = $reflectionModelProperty;
        }

        $columnsInDatabase = $this->database->select($table)
            ->limit(0)
            ->execute()
            ->getColumns();

        $columnsToAdd = [];
        $columnsToDrop = [];

        foreach ($columns as $column => $reflectionModelProperty) {
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

        foreach ($columnsToAdd as $column) {
            $reflectionModelProperty = $columns[$column];

            $propertyAllowsNull = $reflectionModelProperty->allowsNull();
            $propertyDefaultValue = $reflectionModelProperty->getDefaultValue();
            $propertyIsPrimaryKey = $reflectionModelProperty->isPrimaryKey();
            $propertyHasAutoIncrementAttribute = $reflectionModelProperty->isAutoIncrement();

            $columnType = $reflectionModelProperty->getColumnType($this->dialect);
            $defaultValue = $this->getValueIfBackedEnum($propertyDefaultValue);

            $query->addColumn(
                $column,
                $columnType,
                !$propertyAllowsNull,
                $defaultValue,
                $propertyHasAutoIncrementAttribute
            );

            if ($propertyIsPrimaryKey) {
                $query->addPrimaryKeys($column);
            }
        }

        foreach ($columnsToDrop as $column) {
            $query->dropColumn($column);
        }

        $query->execute($emulatePrepare);

        return null;
    }
}
