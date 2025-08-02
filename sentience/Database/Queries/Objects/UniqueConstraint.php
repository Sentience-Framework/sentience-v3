<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class UniqueConstraint
{
    public function __construct(public array $columns, public ?string $name = null)
    {
    }
}
