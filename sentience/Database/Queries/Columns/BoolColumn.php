<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Interfaces\Sql;

class BoolColumn extends ColumnAbstract
{
    public function __construct(
        bool $notNull = false,
        null|bool|Sql $default = null
    ) {
        parent::__construct(null, $notNull, $default, false);
    }
}
