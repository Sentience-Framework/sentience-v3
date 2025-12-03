<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\DataLayer\Database\Objects\ConditionGroup;
use Sentience\DataLayer\Models\Reflection\ReflectionModel;

class DeleteModelsQuery extends ModelsQueryAbstract
{
    use WhereTrait;

    public function __construct(Database $database, DialectInterface $dialect, array $models)
    {
        parent::__construct($database, $dialect, $models);
    }

    public function execute(bool $emulatePrepare = false): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $table = $reflectionModel->getTable();
            // $columns = $reflectionModel->getColumns();

            $deleteQuery = $this->database->delete($table);

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $this->getValueIfBackedEnum($model->{$property});

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $deleteQuery->whereEquals($column, $value);
                }
            }

            $deleteQuery->whereGroup(fn (): ConditionGroup => new ConditionGroup(ChainEnum::AND, $this->where));
            // $deleteQuery->returning($columns);

            $result = $deleteQuery->execute($emulatePrepare);

            // $deletedRow = $result->fetchAssoc();

            // if ($deletedRow) {
            //     $this->mapAssocToModel($model, $deletedRow);
            // }
        }

        return $this->models;
    }
}
