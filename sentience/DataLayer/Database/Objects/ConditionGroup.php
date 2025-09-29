<?php

namespace Sentience\DataLayer\Database\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;

class ConditionGroup extends \Sentience\Database\Queries\Objects\ConditionGroup
{
    public function __construct(public ChainEnum $chain, array $conditions)
    {
        array_push($this->where, ...$conditions);
    }
}
