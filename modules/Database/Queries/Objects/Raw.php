<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

class Raw
{
    public function __construct(public string $expression)
    {
    }
}
