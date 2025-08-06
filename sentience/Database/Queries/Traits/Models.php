<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

use Sentience\Models\Model;

trait Models
{
    protected string|array|Model $models;
}
