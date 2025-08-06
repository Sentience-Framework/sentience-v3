<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

class DropColumn
{
    public function __construct(public string $column)
    {
    }
}
