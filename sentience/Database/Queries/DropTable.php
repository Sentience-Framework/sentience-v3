<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExists;
use Sentience\Database\Results;

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
