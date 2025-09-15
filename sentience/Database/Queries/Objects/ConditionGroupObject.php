<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Traits\WhereTrait;

class ConditionGroupObject
{
    use WhereTrait;

    public function __construct(public ChainEnum $chain)
    {
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
