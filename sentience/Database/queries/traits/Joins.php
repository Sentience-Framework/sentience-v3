<?php

namespace sentience\Database\queries\traits;

use sentience\Database\queries\enums\JoinType;
use sentience\Database\queries\objects\Alias;
use sentience\Database\queries\objects\Join;
use sentience\Database\queries\objects\Raw;
use sentience\Database\queries\Query;

trait Joins
{
    protected array $joins = [];

    public function leftJoin(string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinType::LEFT_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function rightJoin(string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinType::RIGHT_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function innerJoin(string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinType::INNER_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function join(string $expression): static
    {
        $this->joins[] = Query::raw($expression);

        return $this;
    }

    protected function addJoin(JoinType $type, string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): void
    {
        $this->joins[] = new Join($type, $joinTable, $joinTableColumn, $onTable, $onTableColumn);
    }
}
