<?php

namespace sentience\Database\queries\objects;

class DropConstraint
{
    public string $constraint;

    public function __construct(string $constraint)
    {
        $this->constraint = $constraint;
    }
}
