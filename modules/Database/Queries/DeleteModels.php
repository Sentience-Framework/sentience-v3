<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Enums\Chain;
use Modules\Database\Queries\Enums\Operator;
use Modules\Database\Queries\Objects\Condition;
use Modules\Database\Queries\Traits\Where;
use Modules\Models\Mapper;
use Modules\Models\Reflection\ReflectionModel;

class DeleteModels extends ModelsQueryAbstract
{
    use Where;

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
                    $primaryKeyConditions[] = new Condition(
                        Operator::EQUALS,
                        $column,
                        $value,
                        Chain::AND
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
                Mapper::mapAssoc($model, $deletedRow);
            }
        }

        return $this->models;
    }
}
