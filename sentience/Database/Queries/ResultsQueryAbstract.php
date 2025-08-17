<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\Table;

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

        return $this->database->queryWithParams($queryWithParams);
    }
}
