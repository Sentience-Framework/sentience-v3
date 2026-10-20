<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

abstract class ColumnAbstract
{
    public function __construct(
        public ?int $size,
        public bool $notNull,
        public null|bool|int|float|string|Sql $default,
        public bool $generatedByDefaultAsIdentity
    ) {
    }
}
