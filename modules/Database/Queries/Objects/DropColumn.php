<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

class DropColumn
{
    public function __construct(public string $column)
    {
    }
}
