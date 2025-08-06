<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\Returning;
use Sentience\Database\Queries\Traits\Values;
use Sentience\Database\Queries\Traits\Where;
use Sentience\Database\Results;

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

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): Results
    {
        return parent::execute();
    }
}
