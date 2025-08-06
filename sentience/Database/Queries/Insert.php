<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\OnConflict;
use Sentience\Database\Queries\Traits\Returning;
use Sentience\Database\Queries\Traits\Values;
use Sentience\Database\Results;

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

    public function execute(): Results
    {
        return parent::execute();
    }
}
