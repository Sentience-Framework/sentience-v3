<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\Chain;
use Sentience\Database\Queries\Enums\Operator;

class Condition
{
    public function __construct(public Operator $type, public string|array $expression, public mixed $value, public Chain $chain)
    {
    }
}
