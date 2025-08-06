<?php

declare(strict_types=1);

namespace Sentience\Models\Attributes;

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
