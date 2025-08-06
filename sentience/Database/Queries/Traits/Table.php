<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Raw;

trait Table
{
    protected string|array|Alias|Raw $table;
}
