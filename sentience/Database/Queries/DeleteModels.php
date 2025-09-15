<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OperatorEnum;
use Sentience\Database\Queries\Objects\ConditionObject;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Models\Reflection\ReflectionModel;

class DeleteModels extends ModelsQueryAbstract
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

            $primaryKeyConditions = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $model->{$property};

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $primaryKeyConditions[] = new ConditionObject(
                        OperatorEnum::EQUALS,
                        $column,
                        $value,
                        ChainEnum::AND
                    );
                }
            }

            $queryWithParams = $this->dialect->delete([
                'table' => $reflectionModel->getTable(),
                'where' => [...$primaryKeyConditions, ...$this->where],
                'returning' => $reflectionModel->getColumns()
            ]);

            $results = $this->database->queryWithParams($queryWithParams);

            $deletedRow = $results->fetchAssoc();

            if ($deletedRow) {
                $this->mapAssocToModel($model, $deletedRow);
            }
        }

        return $this->models;
    }
}
