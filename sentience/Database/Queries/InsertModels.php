<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Traits\Model;
use sentience\Exceptions\QueryException;
use sentience\Helpers\Reflector;
use sentience\Models\Attributes\AutoIncrement;

class InsertModels extends Insert
{
    use Model;

    public function execute(): array
    {
        if (!$this->model) {
            throw new QueryException('no model set');
        }

        $models = (array) $this->model;

        foreach ($models as $model) {
            $query = clone $this;

            $query->table($model::getTable());

            $columns = $model::getColumns();

            $values = [];

            foreach ($columns as $column => $property) {
                if (!Reflector::isPropertyInitialized($model, $property)) {
                    continue;
                }

                $values[$column] = $model->{$property};
            }

            $query->values($values);

            $query->returning();

            $queryWithParams = $query->build();

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

        return $models;
    }
}
