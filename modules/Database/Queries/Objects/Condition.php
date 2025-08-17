<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

use Modules\Database\Queries\Enums\Chain;
use Modules\Database\Queries\Enums\WhereType;

class Condition
{
    public function __construct(public WhereType $type, public string|array $expression, public mixed $value, public Chain $chain)
    {
    }
}
