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

    public function __construct(Database $database, DialectInterface $dialect, array|Model $models)
    {
        parent::__construct($database, $dialect, '');

        $this->models = $models;
    }

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

        return $this->models;
    }
}
