<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Enums\Config;
use Modules\Models\Mapper;
use Modules\Models\Reflection\ReflectionModel;

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
        foreach ($this->model as $model) {
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
                'table' => $this->table,
                'values' => $values,
                'returning' => []
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
                Mapper::mapAssoc($model, $insertedRow);
            }

            $lastInsertId = $results->lastInsertId();

            if (!$lastInsertId) {
                continue;
            }

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isAutoIncrement()) {
                    continue;
                }

                $model->{$reflectionModelProperty->getProperty()} = $lastInsertId;
            }
        }

        return $this->model;
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

    public function excludeColumn(string $column): static
    {
        $this->excludeColumnsOnUpdate[] = $column;

        return $this;
    }

    public function excludeColumns(array $columns): static
    {
        $this->excludeColumnsOnUpdate = array_merge($this->excludeColumnsOnUpdate, $columns);

        return $this;
    }
}
