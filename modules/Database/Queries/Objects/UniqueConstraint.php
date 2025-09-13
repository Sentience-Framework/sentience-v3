<?php

namespace Modules\Database\Queries\Objects;

class UniqueConstraint
{
    public function __construct(public array $columns, public ?string $name = null)
    {
    }
}
