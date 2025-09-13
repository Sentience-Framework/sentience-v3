<?php

namespace Modules\Database\Queries\Objects;

class DropConstraint
{
    public function __construct(public string $constraint)
    {
    }
}
