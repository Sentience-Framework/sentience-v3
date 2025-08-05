<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Traits\Model;
use sentience\Exceptions\QueryException;

class SelectModels extends Select
{
    use Model;

    public function execute(): array
    {
        if (!$this->model) {
            throw new QueryException('no model set');
        }

        $this->table($this->model::getTable());
        $this->columns(array_keys($this->model::getColumns()));

        $results = parent::execute();

        return array_map(
            fn(array $row): object => (new $this->model)->fromArray($row),
            $results->fetchAllAssoc()
        );
    }
}
