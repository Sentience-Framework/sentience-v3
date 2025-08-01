<?php

namespace sentience\Database\queries\objects;

use sentience\Database\queries\enums\Chain;
use sentience\Database\queries\traits\Where;

class ConditionGroup
{
    use Where;

    public Chain $chain;

    public function __construct(Chain $chain)
    {
        $this->chain = $chain;
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
