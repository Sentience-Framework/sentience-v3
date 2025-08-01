<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\traits\OnConflict;
use sentience\Database\queries\traits\Returning;
use sentience\Database\queries\traits\Table;
use sentience\Database\queries\traits\Values;

class Insert extends Query
{
    use OnConflict;
    use Returning;
    use Table;
    use Values;

    public function build(): QueryWithParams
    {
        return $this->dialect->insert([
            'table' => $this->table,
            'values' => $this->values,
            'onConflict' => [
                'conflict' => $this->onConflict,
                'updates' => $this->onConflictUpdates,
                'primaryKey' => $this->onConflictPrimaryKey
            ],
            'returning' => $this->returning
        ]);
    }
}
