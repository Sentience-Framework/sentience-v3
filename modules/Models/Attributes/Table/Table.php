<?php

declare(strict_types=1);

namespace Modules\Models\Attributes\Table;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public string $table,
        public ?string $alias = null
    ) {
    }
}
