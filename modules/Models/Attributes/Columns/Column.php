<?php

declare(strict_types=1);

namespace Modules\Models\Attributes\Columns;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $column,
        public mixed $default = null
    ) {
    }
}
