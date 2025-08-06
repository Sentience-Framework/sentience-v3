<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectFactory;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Traits\Models;
use sentience\Exceptions\QueryException;
use sentience\Models\Model;

class DeleteModels extends Delete
{
    use Models;

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

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $column => $property) {
                $query->whereEquals($column, $model->{$property});
            }

            $query->returning();

            $queryWithParams = $query->build();

            $results = $this->database->prepared($queryWithParams->query, $queryWithParams->params);

            $deletedRow = $results->fetchAssoc();

            if ($deletedRow) {
                $model->fromArray($deletedRow);
            }
        }

        return $this->models;
    }
}
