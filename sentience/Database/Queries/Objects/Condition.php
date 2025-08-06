<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\Chain;
use Sentience\Database\Queries\Enums\WhereType;

class Condition
{
    public function __construct(public WhereType $type, public string|array $expression, public mixed $value, public Chain $chain)
    {
    }
}
