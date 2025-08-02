<?php

declare(strict_types=1);

namespace src\Models\Attributes;

class Column
{
    public function __construct(
        public string $column,
        public mixed $default = null
    ) {
    }
}
