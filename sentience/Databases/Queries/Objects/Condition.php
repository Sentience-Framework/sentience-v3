<?php

namespace sentience\Database\queries\objects;

use sentience\Database\queries\enums\Chain;
use sentience\Database\queries\enums\WhereType;

class Condition
{
    public WhereType $type;
    public string|array $expression;
    public mixed $value;
    public Chain $chain;

    public function __construct(WhereType $type, string|array $expression, mixed $value, Chain $chain)
    {
        $this->type = $type;
        $this->expression = $expression;
        $this->value = $value;
        $this->chain = $chain;
    }
}
