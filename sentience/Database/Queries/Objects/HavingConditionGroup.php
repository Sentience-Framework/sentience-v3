<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Traits\HavingTrait;

class HavingConditionGroup extends ConditionGroup
{
    use HavingTrait;

    public function getConditions(): array
    {
        return $this->having;
    }
}
