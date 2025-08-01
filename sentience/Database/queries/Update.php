<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\traits\Limit;
use sentience\Database\queries\traits\Returning;
use sentience\Database\queries\traits\Table;
use sentience\Database\queries\traits\Values;
use sentience\Database\queries\traits\Where;

class Update extends Query
{
    use Limit;
    use Returning;
    use Table;
    use Values;
    use Where;

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
