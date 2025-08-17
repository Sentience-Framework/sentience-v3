<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Traits\Where;

class DeleteModels extends ModelsQueryAbstract
{
    use Where;

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $query = $this->database->delete($model::getTable());

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $column => $property) {
                $query->whereEquals($column, $model->{$property});
            }

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->queryWithParams($queryWithParams->query);

            $deletedRow = $results->fetchAssoc();

            if ($deletedRow) {
                $model->fromDatabase($deletedRow);
            }
        }

        return $this->models;
    }
}
