<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\JoinType;

class Join
{
    public string|array|Raw $joinTable;
    public ?string $joinTableAlias = null;

    public function __construct(public JoinType $type, string|array|Alias|Raw $joinTable, public string $joinTableColumn, public string|array|Raw $onTable, public string $onTableColumn)
    {
        if ($joinTable instanceof Alias) {
            $this->joinTable = $joinTable->name;
            $this->joinTableAlias = $joinTable->alias;
        } else {
            $this->joinTable = $joinTable;
        }
    }
}
