<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\traits\Returning;
use sentience\Database\queries\traits\Table;
use sentience\Database\queries\traits\Where;

class Delete extends Query
{
    use Returning;
    use Table;
    use Where;

    public function build(): QueryWithParams
    {
        return $this->dialect->delete([
            'table' => $this->table,
            'where' => $this->where,
            'returning' => $this->returning
        ]);
    }
}
