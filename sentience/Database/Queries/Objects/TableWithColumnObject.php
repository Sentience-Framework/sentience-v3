<?php

namespace Sentience\Database\Queries\Objects;

class TableWithColumnObject
{
    public function __construct(
        public string|array|AliasObject|RawObject $table,
        public string|AliasObject|RawObject $column
    ) {
    }
}
