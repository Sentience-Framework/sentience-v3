<?php

namespace Sentience\ORM\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class CreateModelQuery extends ModelsQueryAbstract
{
    use IfNotExistsTrait;

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
            if ($reflectionModelProperty->getRelation()) {
                continue;
            }

            $propertyAllowsNull = $reflectionModelProperty->allowsNull();
            $propertyDefaultValue = $reflectionModelProperty->getDefaultValue();
            $propertyHasAutoIncrementAttribute = $reflectionModelProperty->isAutoIncrement();

            $column = $reflectionModelProperty->getColumn();
            $columnType = $reflectionModelProperty->getColumnType($this->dialect);
            $defaultValue = $this->getValueIfBackedEnum($propertyDefaultValue);

            $query->column(
                $column,
                $columnType,
                !$propertyAllowsNull,
                $defaultValue,
                $propertyHasAutoIncrementAttribute && $this->dialect instanceof MySQLDialect
                ? ['AUTO_INCREMENT']
                : []
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
