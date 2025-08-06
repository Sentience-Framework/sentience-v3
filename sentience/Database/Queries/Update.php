<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Traits\Returning;
use sentience\Database\Queries\Traits\Values;
use sentience\Database\Queries\Traits\Where;
use sentience\Database\Results;

class Update extends ResultsQueryAbstract
{
    use Returning;
    use Values;
    use Where;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->update([
            'table' => $this->table,
            'values' => $this->values,
            'where' => $this->where,
            'returning' => $this->returning
        ]);
    }

    public function execute(): Results
    {
        return parent::execute();
    }
}
