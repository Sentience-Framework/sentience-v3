<?php

namespace src\Models\Attributes;

class Table
{
    public function __construct(
        public string $table
    ) {
    }
}
