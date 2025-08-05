<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

use Attribute;

#[Attribute]
class PrimaryKeys
{
    public function __construct(public array $properties)
    {
    }
}
