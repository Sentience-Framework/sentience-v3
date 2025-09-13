<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\IfNotExists;
use Sentience\Models\Reflection\ReflectionModel;

class CreateModel extends ModelsQueryAbstract
{
    use IfNotExists;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(): null
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);
        $reflectionModelProperties = $reflectionModel->getProperties();

        $table = $reflectionModel->getTable();
        $primaryKeys = $reflectionModel->getPrimaryKeys();
        $uniqueConstraint = $reflectionModel->getUniqueConstraint();

        $query = $this->database->createTable($table)
            ->primaryKeys($primaryKeys);

        if ($this->ifNotExists) {
            $query->ifNotExists();
        }

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $propertyAllowsNull = $reflectionModelProperty->allowsNull();
            $propertyDefaultValue = $reflectionModelProperty->getDefaultValue();
            $propertyHasAutoIncrementAttribute = $reflectionModelProperty->isAutoIncrement();

            $column = $reflectionModelProperty->getColumn();
            $columnType = $reflectionModelProperty->getColumnType($this->dialect);

            $query->column(
                $column,
                $columnType,
                !$propertyAllowsNull,
                $propertyDefaultValue,
                $propertyHasAutoIncrementAttribute
            );
        }

        if ($uniqueConstraint) {
            $query->uniqueConstraint(
                $uniqueConstraint->columns,
                $uniqueConstraint->name
            );
        }

        $query->execute();

        return null;
    }
}
