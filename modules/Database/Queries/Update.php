<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\Returning;
use Modules\Database\Queries\Traits\Values;
use Modules\Database\Queries\Traits\Where;
use Modules\Database\Results;

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
