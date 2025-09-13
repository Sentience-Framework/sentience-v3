<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Raw;

abstract class ResultsQueryAbstract extends Query implements ResultsQueryInterface
{
    public function __construct(Database $database, DialectInterface $dialect, protected string|array|Alias|Raw $table)
    {
        parent::__construct($database, $dialect);
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
