<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\IfExists;
use Modules\Database\Results;

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

    public function execute(): Results
    {
        return parent::execute();
    }
}
