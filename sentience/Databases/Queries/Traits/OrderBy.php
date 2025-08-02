<?php

namespace sentience\Database\queries\traits;

use sentience\Database\queries\enums\OrderByDirection;
use sentience\Database\queries\objects\OrderBy as OrderByObject;
use sentience\Database\queries\objects\Raw;

trait OrderBy
{
    protected array $orderBy = [];

    public function orderByAsc(string|array|Raw $column): static
    {
        $this->addOrderBy($column, OrderByDirection::ASC);

        return $this;
    }

    public function orderByDesc(string|array|Raw $column): static
    {
        $this->addOrderBy($column, OrderByDirection::DESC);

        return $this;
    }

    protected function addOrderBy(string|array|Raw $column, OrderByDirection $direction): void
    {
        $this->orderBy[] = new OrderByObject($column, $direction);
    }
}
