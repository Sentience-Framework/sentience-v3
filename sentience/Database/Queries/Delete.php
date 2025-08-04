<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Traits\Returning;
use sentience\Database\Queries\Traits\Table;
use sentience\Database\Queries\Traits\Where;
use sentience\Database\Results;

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

    public function execute(): Results
    {
        return parent::execute();
    }
}
