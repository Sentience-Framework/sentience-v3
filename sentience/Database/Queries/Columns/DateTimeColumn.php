<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

class DateTimeColumn extends ColumnAbstract
{
    public function __construct(
        int $precision = 6,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($precision, $notNull, $default, false);
    }
}
