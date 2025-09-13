<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\OrderByDirection;

class OrderBy
{
    public function __construct(public string|array|Raw $column, public OrderByDirection $direction)
    {
    }
}
