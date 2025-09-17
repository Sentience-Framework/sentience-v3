<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Models\Reflection\ReflectionModel;

class DeleteModelsQuery extends ModelsQueryAbstract
{
    use WhereTrait;

    public function __construct(Database $database, DialectInterface $dialect, array $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $table = $reflectionModel->getTable();
            $columns = $reflectionModel->getColumns();

            $deleteQuery = $this->database->delete($table);

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $this->value($model->{$property});

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $deleteQuery->whereEquals($column, $value);
                }
            }

            $deleteQuery->whereGroup(fn (): ConditionGroup => new ConditionGroup(ChainEnum::AND, $this->where));
            $deleteQuery->returning($columns);

            $results = $deleteQuery->execute();

            $deletedRow = $results->fetchAssoc();

            if ($deletedRow) {
                $this->mapAssocToModel($model, $deletedRow);
            }
        }

        return $this->models;
    }
}
