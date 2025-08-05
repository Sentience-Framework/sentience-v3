<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

use Attribute;

#[Attribute]
class Column
{
    public function __construct(
        public string $column,
        public mixed $default = null
    ) {
    }
}
