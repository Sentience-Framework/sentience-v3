<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

use Modules\Database\Queries\Objects\Alias;
use Modules\Database\Queries\Objects\Raw;

trait Table
{
    protected string|array|Alias|Raw $table;
}
