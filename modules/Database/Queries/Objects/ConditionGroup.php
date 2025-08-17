<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

use Modules\Database\Queries\Enums\Chain;
use Modules\Database\Queries\Traits\Where;

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
