<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Traits\Models;
use sentience\Helpers\Reflector;
use sentience\Models\Attributes\AutoIncrement;
use sentience\Models\Model;

class InsertModels extends Insert
{
    use Models;

    protected ?bool $onDuplicateUpdate = null;
    protected array $excludeColumnsOnUpdate = [];

    public function __construct(Database $database, DialectInterface $dialect, array|Model $models)
    {
        parent::__construct($database, $dialect, '');

        $this->models = !is_array($models) ? [$models] : $models;
    }

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $query = clone $this;

            $query->table = $model::getTable();

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
                $model->fromArray($insertedRow);
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
