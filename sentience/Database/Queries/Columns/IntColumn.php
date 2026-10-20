<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

class IntColumn extends ColumnAbstract
{
    public function __construct(
        int $bits = 64,
        bool $notNull = false,
        null|int|Sql $default = null,
        bool $generatedByDefaultAsIdentity = false
    ) {
        parent::__construct($bits, $notNull, $default, $generatedByDefaultAsIdentity);
    }
}
