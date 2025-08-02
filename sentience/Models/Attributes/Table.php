<?php

declare(strict_types=1);

namespace src\Models\Attributes;

class Table
{
    public function __construct(
        public string $table
    ) {
    }
}
