<?php

namespace Sentience\ORM\Database\Queries;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\ORM\Models\Reflection\ReflectionModel;
use Sentience\Timestamp\Timestamp;

class CreateModelQuery extends ModelsQueryAbstract
{
    use IfNotExistsTrait;

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

            $column = $reflectionModelProperty->getColumn();
            $propertyType = $reflectionModelProperty->getType();
            $propertyAllowsNull = $reflectionModelProperty->allowsNull();
            $propertyDefaultValue = $reflectionModelProperty->getDefaultValue();

            $defaultValue = $this->getValueIfBackedEnum($propertyDefaultValue);

            if ($reflectionModelProperty->isAutoIncrement()) {
                $query->identity($column, 64);

                continue;
            }

            match ($propertyType) {
                'bool' => $query->bool($column, !$propertyAllowsNull, $defaultValue),
                'int' => $query->int($column, 64, !$propertyAllowsNull, $defaultValue),
                'float' => $query->float($column, 64, !$propertyAllowsNull, $defaultValue),
                'string' => $query->string(
                    $column,
                    $this->getTextSizeForColumn($reflectionModelProperty),
                    !$propertyAllowsNull,
                    $defaultValue
                ),
                DateTime::class,
                DateTimeImmutable::class,
                Timestamp::class => $query->dateTime($column, 6, !$propertyAllowsNull, $defaultValue)
            };
        }

        if ($uniqueConstraint) {
            $query->uniqueConstraint(
                $uniqueConstraint->columns,
                $uniqueConstraint->name
            );
        }

        $query->execute($emulatePrepare);

        return null;
    }
}
