<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class AlterColumn
{
    public function __construct(public string $column, public string $options)
    {
    }
}
