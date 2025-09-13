<?php

namespace Modules\Database\Queries\Objects;

class Alias
{
    public function __construct(public string|array|Raw $name, public string $alias)
    {
    }
}
