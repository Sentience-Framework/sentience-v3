<?php

namespace Sentience\ORM\Database\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;

class ConditionGroup extends \Sentience\Database\Queries\Objects\ConditionGroup
{
    public function __construct(ChainEnum $chain, array $conditions)
    {
        parent::__construct($chain);

        array_push($this->where, ...$conditions);
    }
}
