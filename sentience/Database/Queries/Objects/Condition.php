<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

use sentience\Database\Queries\Enums\Chain;
use sentience\Database\Queries\Enums\WhereType;

class Condition
{
    public function __construct(public WhereType $type, public string|array $expression, public mixed $value, public Chain $chain)
    {
    }
}
