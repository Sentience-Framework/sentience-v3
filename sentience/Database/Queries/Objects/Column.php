<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class Column
{
    public function __construct(public string $name, public string $type, public bool $notNull = false, public mixed $defaultValue = null, public bool $autoIncrement = false)
    {
    }
}
