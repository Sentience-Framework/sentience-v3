<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\JoinType;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

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
