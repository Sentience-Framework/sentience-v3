<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

use sentience\Models\Model;

trait Models
{
    protected string|array|Model $models;
}
