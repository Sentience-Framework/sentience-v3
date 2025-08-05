<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

use Attribute;

#[Attribute]
class Table
{
    public function __construct(
        public string $table,
        public ?string $alias = null
    ) {
    }
}
