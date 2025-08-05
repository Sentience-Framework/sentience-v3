<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Traits\Limit;
use sentience\Database\Queries\Traits\Returning;
use sentience\Database\Queries\Traits\Table;
use sentience\Database\Queries\Traits\Values;
use sentience\Database\Queries\Traits\Where;
use sentience\Database\Results;

class Update extends Query
{
    use Limit;
    use Returning;
    use Table;
    use Values;
    use Where;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Alias|Raw $table)
    {
        parent::__construct($database, $dialect);

        $this->table = $table;
    }

    public function build(): QueryWithParams
    {
        return $this->dialect->update([
            'table' => $this->table,
            'values' => $this->values,
            'where' => $this->where,
            'limit' => $this->limit,
            'returning' => $this->returning
        ]);
    }
}
