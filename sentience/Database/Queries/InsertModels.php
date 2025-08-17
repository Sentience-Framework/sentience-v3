<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\Columns\AutoIncrement;
use Sentience\Models\Reflection\ReflectionModel;

class InsertModels extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $excludeColumnsOnUpdate = [];

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $query = $this->database->insert($model::getTable());

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $values = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();

                $values[$column] = $model->{$property};
            }

            $query->values($values);

            if (!is_null($this->onDuplicateUpdate) && $uniqueConstraint = $reflectionModel->getUniqueConstraint()) {
                !$this->onDuplicateUpdate
                    ? $query->onConflictIgnore($uniqueColumns)
                    : $query->onConflictUpdate(
                        $uniqueConstraint->columns,
                        array_filter(
                            $values,
                            fn(string $column): bool => !in_array(
                                $column,
                                Arrays::unique($this->excludeColumnsOnUpdate)
                            ),
                            ARRAY_FILTER_USE_KEY
                        )
                    );
            }

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->queryWithParams($queryWithParams->query);

            $insertedRow = $results->fetchAssoc();

            if ($insertedRow) {
                $model->fromDatabase($insertedRow);
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
