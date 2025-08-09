<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\Columns\AutoIncrement;

class InsertModels extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $excludeColumnsOnUpdate = [];

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $query = $this->database->insert($model::getTable());

            $columns = $model::getColumns();

            $values = [];

            foreach ($columns as $column => $property) {
                if (!Reflector::isPropertyInitialized($model, $property)) {
                    continue;
                }

                $values[$column] = $model->{$property};
            }

            $query->values($values);

            if (!is_null($this->onDuplicateUpdate)) {
                $uniqueColumns = array_keys($model::getUniqueColumns());

                !$this->onDuplicateUpdate
                    ? $query->onConflictIgnore($uniqueColumns)
                    : $query->onConflictUpdate(
                        $uniqueColumns,
                        array_filter(
                            $values,
                            fn(string $column): bool => !in_array($column, $this->excludeColumnsOnUpdate),
                            ARRAY_FILTER_USE_KEY
                        )
                    );
            }

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->prepared($queryWithParams->query, $queryWithParams->params);

            $insertedRow = $results->fetchAssoc();

            if ($insertedRow) {
                $model->fromDatabase($insertedRow);
            }

            $lastInsertId = $results->lastInsertId();

            if (!$lastInsertId) {
                continue;
            }

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $property) {
                if (!Reflector::propertyHasAttribute($model, $property, AutoIncrement::class)) {
                    continue;
                }

                $model->{$property} = $lastInsertId;
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
