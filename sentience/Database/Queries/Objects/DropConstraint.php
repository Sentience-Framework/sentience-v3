<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class DropConstraint
{
    public function __construct(public string $constraint)
    {
    }
}
