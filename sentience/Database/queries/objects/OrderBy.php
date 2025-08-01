<?php

namespace sentience\Database\queries\objects;

use sentience\Database\queries\enums\OrderByDirection;

class OrderBy
{
    public string|array|Raw $column;
    public OrderByDirection $direction;

    public function __construct(string|array|Raw $column, OrderByDirection $direction)
    {
        $this->column = $column;
        $this->direction = $direction;
    }
}
