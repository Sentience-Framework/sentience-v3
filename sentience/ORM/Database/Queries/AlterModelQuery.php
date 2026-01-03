<?php

namespace Sentience\ORM\Database\Queries;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\ORM\Models\Reflection\ReflectionModel;
use Sentience\Timestamp\Timestamp;

class AlterModelQuery extends ModelsQueryAbstract
{
    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(bool $emulatePrepares = false): null
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);
        $reflectionModelProperties = $reflectionModel->getProperties();

        $table = $reflectionModel->getTable();
        $primaryKeys = $reflectionModel->getPrimaryKeys();

        $query = $this->database->alterTable($table)
            ->addPrimaryKeys($primaryKeys);

        $columns = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $column = $reflectionModelProperty->getColumn();

            $columns[$column] = $reflectionModelProperty;
        }

        $columnsInDatabase = $this->database->select($table)
            ->limit(0)
            ->execute()
            ->columns();

        $columnsToAdd = [];
        $columnsToDrop = [];

        foreach ($columns as $column => $reflectionModelProperty) {
            if (array_key_exists($column, $columnsInDatabase)) {
                continue;
            }

            $columnsToAdd[] = $column;
        }

        foreach ($columnsInDatabase as $column => $type) {
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

            if ($reflectionModelProperty->getRelation()) {
                continue;
            }

            $propertyType = $reflectionModelProperty->getType();
            $propertyAllowsNull = $reflectionModelProperty->allowsNull();
            $propertyDefaultValue = $reflectionModelProperty->getDefaultValue();

            $defaultValue = $this->getValueIfBackedEnum($propertyDefaultValue);

            if ($reflectionModelProperty->isAutoIncrement()) {
                $query->addIdentity($column, 64);

                continue;
            }

            match ($propertyType) {
                'bool' => $query->addBool($column, !$propertyAllowsNull, $defaultValue),
                'int' => $query->addInt($column, 64, !$propertyAllowsNull, $defaultValue),
                'float' => $query->addFloat($column, 64, !$propertyAllowsNull, $defaultValue),
                'string' => $query->addString(
                    $column,
                    $this->getTextSizeForColumn($reflectionModelProperty),
                    !$propertyAllowsNull,
                    $defaultValue
                ),
                DateTime::class,
                DateTimeImmutable::class,
                Timestamp::class => $query->addDateTime($column, 6, !$propertyAllowsNull, $defaultValue)
            };
        }

        foreach ($columnsToDrop as $column) {
            $query->dropColumn($column);
        }

        $query->execute($emulatePrepares);

        return null;
    }
}
