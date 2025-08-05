<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use DateTime;
use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Traits\Models;
use sentience\Exceptions\QueryException;
use sentience\Helpers\Arrays;
use sentience\Helpers\Reflector;
use sentience\Models\Attributes\AutoIncrement;

class UpdateModels extends Update
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, array|Models $models)
    {
        parent::__construct($database, $dialect, '');

        $this->models = $models;
    }

    protected array $updates = [];

    public function execute(): array
    {
        foreach ((array) $this->models as $model) {
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

            $query->values([
                ...$values,
                ...$this->updates
            ]);

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $column => $property) {
                $query->whereEquals($column, $model->{$property});
            }

            $query->returning();

            $queryWithParams = $query->build();

            $results = $this->database->prepared($queryWithParams->query, $queryWithParams->params);

            $updatedRow = $results->fetchAssoc();

            if ($updatedRow) {
                $model->fromArray($updatedRow);
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

    public function updateColumns(array $columns): static
    {
        array_merge($this->updates, $columns);

        return $this;
    }

    public function updateColumn(string $column, null|bool|int|float|string|DateTime $value): static
    {
        $this->updates[$column] = $value;

        return $this;
    }
}
