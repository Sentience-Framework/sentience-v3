<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

class StringColumn extends ColumnAbstract
{
    public function __construct(
        int $length = 255,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($length, $notNull, $default, false);
    }
}
