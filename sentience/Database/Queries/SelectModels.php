<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Traits\Models;
use sentience\Exceptions\QueryException;

class SelectModels extends Select
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, string $models)
    {
        parent::__construct($database, $dialect, '');

        $this->models = $models;
    }

    public function execute(): array
    {
        $this->table = $this->models::getTable();
        $this->columns(array_keys($this->models::getColumns()));

        $results = parent::execute();

        return array_map(
            fn(array $row): object => (new $this->models)->fromArray($row),
            $results->fetchAllAssoc()
        );
    }
}
