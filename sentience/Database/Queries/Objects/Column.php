<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ColumnTypeEnum;

class Column
{
    public function __construct(
        public string $name,
        public string|ColumnTypeEnum $type,
        public bool $notNull,
        public mixed $default,
        public bool $generatedByDefaultAsIdentity
    ) {
    }
}
