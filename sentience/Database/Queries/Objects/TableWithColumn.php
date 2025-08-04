<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Objects;

class TableWithColumn
{
    public function __construct(
        public string|array|Alias|Raw $table,
        public string|Alias|Raw $column
    ) {
    }
}
