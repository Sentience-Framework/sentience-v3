<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Objects\Alias;
use Modules\Database\Queries\Objects\Raw;
use Modules\Database\Queries\Traits\Table;

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
