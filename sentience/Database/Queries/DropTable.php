<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Traits\IfExists;
use sentience\Database\Queries\Traits\Table;
use sentience\Database\Results;

class DropTable extends Query
{
    use IfExists;
    use Table;

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
