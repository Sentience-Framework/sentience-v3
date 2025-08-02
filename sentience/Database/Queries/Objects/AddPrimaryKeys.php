<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class AddPrimaryKeys
{
    public function __construct(public array $columns)
    {
    }
}
