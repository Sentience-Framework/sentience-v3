<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Models\Reflection\ReflectionModel;

class InsertModels extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $excludeColumnsOnUpdate = [];

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

            $values = [];
            $autoIncrementPrimaryKeyColumn = null;

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();

                $values[$column] = $model->{$property};

                if ($reflectionModelProperty->isAutoIncrement()) {
                    $autoIncrementPrimaryKeyColumn = $column;
                }
            }

            $config = [
                'table' => $reflectionModel->getTable(),
                'values' => $values,
                'returning' => $reflectionModel->getColumns()
            ];

            if (!is_null($this->onDuplicateUpdate) && $uniqueConstraint = $reflectionModel->getUniqueConstraint()) {
                $config['onConflict'] = [
                    'conflict' => $uniqueConstraint->columns,
                    'updates' => $values,
                    'primaryKey' => $autoIncrementPrimaryKeyColumn
                ];
            }

            $queryWithParams = $this->dialect->insert($config);

            $results = $this->database->queryWithParams($queryWithParams);

            $insertedRow = $results->fetchAssoc();

            if ($insertedRow) {
                $this->mapAssocToModel($model, $insertedRow);
            }

            $lastInsertId = $this->database->lastInsertId();

            if (!$lastInsertId) {
                continue;
            }

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isAutoIncrement()) {
                    continue;
                }

                $model->{$reflectionModelProperty->getProperty()} = (int) $lastInsertId;
            }
        }

        return $this->models;
    }

    public function onDuplicateIgnore(): static
    {
        $this->onDuplicateUpdate = false;

        return $this;
    }

    public function onDuplicateUpdate(array $excludeColumns = []): static
    {
        $this->onDuplicateUpdate = true;
        $this->excludeColumnsOnUpdate = $excludeColumns;

        return $this;
    }
}
