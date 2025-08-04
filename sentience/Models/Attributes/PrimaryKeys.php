<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

class PrimaryKeys
{
    public function __construct(public array $properties)
    {
    }
}
