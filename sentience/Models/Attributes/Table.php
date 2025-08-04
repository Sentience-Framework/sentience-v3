<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

class Table
{
    public function __construct(
        public string $table,
        public ?string $alias = null
    ) {
    }
}
