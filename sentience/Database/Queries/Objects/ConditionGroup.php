<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\Chain;
use Sentience\Database\Queries\Traits\Where;

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
