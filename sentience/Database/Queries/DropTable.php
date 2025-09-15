<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\ResultsInterface;

class DropTable extends ResultsQueryAbstract
{
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParamsObject
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
