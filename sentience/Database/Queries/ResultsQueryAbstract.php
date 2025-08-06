<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Traits\Table;

abstract class ResultsQueryAbstract extends Query implements ResultsQueryInterface
{
    use Table;

    public function __construct(protected Database $database, protected DialectInterface $dialect, string|array|Alias|Raw $table)
    {
        $this->table = $table;
    }

    public function toRawQuery(): string|array
    {
        return $this->toQueryWithParams()->toRawQuery($this->dialect);
    }

    public function execute(): mixed
    {
        $queryWithParams = $this->toQueryWithParams();

        return $this->database->prepared(
            $queryWithParams->query,
            $queryWithParams->params
        );
    }
}
