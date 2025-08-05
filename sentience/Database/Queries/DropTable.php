<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Traits\IfExists;
use sentience\Database\Queries\Traits\Table;
use sentience\Database\Results;

class DropTable extends Query
{
    use IfExists;
    use Table;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Alias|Raw $table)
    {
        parent::__construct($database, $dialect);

        $this->table = $table;
    }

    public function build(): QueryWithParams
    {
        return $this->dialect->dropTable([
            'ifExists' => $this->ifExists,
            'table' => $this->table
        ]);
    }

    public function execute(): Results
    {
        return parent::execute();
    }
}
