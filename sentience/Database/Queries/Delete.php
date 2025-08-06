<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\Returning;
use Sentience\Database\Queries\Traits\Where;
use Sentience\Database\Results;

class Delete extends ResultsQueryAbstract
{
    use Returning;
    use Where;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->delete([
            'table' => $this->table,
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
