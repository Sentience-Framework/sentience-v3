<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Traits\OnConflict;
use sentience\Database\Queries\Traits\Returning;
use sentience\Database\Queries\Traits\Values;
use sentience\Database\Results;

class Insert extends ResultsQueryAbstract
{
    use OnConflict;
    use Returning;
    use Values;

    public function toQueryWithParams(): QueryWithParams
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

    public function execute(): Results
    {
        return parent::execute();
    }
}
