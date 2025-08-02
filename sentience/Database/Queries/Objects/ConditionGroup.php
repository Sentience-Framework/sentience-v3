<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

use sentience\Database\Queries\Enums\Chain;
use sentience\Database\Queries\Traits\Where;

class ConditionGroup
{
    use Where;

    public function __construct(public Chain $chain)
    {
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
