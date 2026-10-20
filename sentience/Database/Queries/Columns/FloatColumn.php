<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

class FloatColumn extends ColumnAbstract
{
    public function __construct(
        int $bits = 64,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($bits, $notNull, $default, false);
    }
}
