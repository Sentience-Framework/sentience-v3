<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\Raw;

trait Table
{
    protected string|array|Alias|Raw $table;
}
