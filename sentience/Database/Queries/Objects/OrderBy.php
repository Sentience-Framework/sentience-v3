<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

use sentience\Database\Queries\Enums\OrderByDirection;

class OrderBy
{
    public function __construct(public string|array|Raw $column, public OrderByDirection $direction)
    {
    }
}
