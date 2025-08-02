<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

use sentience\Database\Queries\Enums\OrderByDirection;
use sentience\Database\Queries\Objects\OrderBy as OrderByObject;
use sentience\Database\Queries\Objects\Raw;

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
