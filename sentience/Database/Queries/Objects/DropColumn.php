<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class DropColumn
{
    public function __construct(public string $column)
    {
    }
}
