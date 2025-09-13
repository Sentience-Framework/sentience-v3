<?php

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\OnConflict;
use Modules\Database\Queries\Traits\Returning;
use Modules\Database\Queries\Traits\Values;
use Modules\Database\Results;
use Modules\Database\Results\ResultsInterface;

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

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultsInterface
    {
        return parent::execute();
    }
}
