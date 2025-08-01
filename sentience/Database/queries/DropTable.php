<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\traits\IfExists;
use sentience\Database\queries\traits\Table;

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
}
