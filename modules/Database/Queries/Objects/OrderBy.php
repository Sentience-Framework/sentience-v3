<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

use Modules\Database\Queries\Enums\OrderByDirection;

class OrderBy
{
    public function __construct(public string|array|Raw $column, public OrderByDirection $direction)
    {
    }
}
