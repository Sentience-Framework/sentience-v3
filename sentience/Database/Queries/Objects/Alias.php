<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

class Alias
{
    public function __construct(public string|array|Raw $name, public string $alias)
    {
    }
}
