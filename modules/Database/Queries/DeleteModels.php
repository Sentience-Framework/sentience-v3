<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Traits\Where;
use Modules\Models\Mapper;

class DeleteModels extends ModelsQueryAbstract
{
    use Where;

    public function __construct(Database $database, DialectInterface $dialect, array $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): array
    {
        foreach ($this->model as $model) {
            $this->validateModel($model);

            $query = $this->database->delete($model::getTable());

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $column => $property) {
                $query->whereEquals($column, $model->{$property});
            }

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->queryWithParams($queryWithParams);

            $deletedRow = $results->fetchAssoc();

            if ($deletedRow) {
                Mapper::mapAssoc($model, $deletedRow);
            }
        }

        return $this->model;
    }
}
