<?php

declare(strict_types=1);

namespace Sentience\Models\Attributes\Table;

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
