<?php

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\IfExists;
use Modules\Database\Results;
use Modules\Database\Results\ResultsInterface;

class DropTable extends ResultsQueryAbstract
{
    use IfExists;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropTable([
            'ifExists' => $this->ifExists,
            'table' => $this->table
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
