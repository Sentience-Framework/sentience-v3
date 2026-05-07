<?php

namespace Sentience\ORM\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Objects\WhereGroup;

class ConditionGroup extends WhereGroup
{
    public function __construct(ChainEnum $chain, bool $not)
    {
        parent::__construct($chain, $not);
    }

    public function addConditions(array $conditions): static
    {
        array_push($this->where, ...$conditions);

        return $this;
    }
}
