<?php

namespace Modules\Database\Queries\Objects;

use Modules\Database\Queries\Enums\Chain;
use Modules\Database\Queries\Enums\Operator;

class Condition
{
    public function __construct(public Operator $type, public string|array $expression, public mixed $value, public Chain $chain)
    {
    }
}
