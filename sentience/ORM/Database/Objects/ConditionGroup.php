<?php

namespace Sentience\ORM\Database\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Objects\WhereGroup;

class ConditionGroup extends WhereGroup
{
    public function __construct(ChainEnum $chain)
    {
        parent::__construct($chain);
    }

    public function addConditions(array $conditions): static
    {
        array_push($this->where, ...$conditions);

        return $this;
    }
}
