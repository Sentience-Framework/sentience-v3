<?php

namespace Sentience\ORM\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;

class ConditionGroup extends \Sentience\Database\Queries\Objects\ConditionGroup
{
    public function __construct(ChainEnum $chain, array $whereMacros, array $conditions)
    {
        parent::__construct($chain, $whereMacros);

        array_push($this->where, ...$conditions);
    }
}
