<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\Returning;
use Modules\Database\Queries\Traits\Where;
use Modules\Database\Results;

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
