<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

class DropConstraint
{
    public function __construct(public string $constraint)
    {
    }
}
