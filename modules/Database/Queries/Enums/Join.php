<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Enums;

enum Join: string
{
    case LEFT_JOIN = 'LEFT JOIN';
    case RIGHT_JOIN = 'RIGHT JOIN';
    case INNER_JOIN = 'INNER JOIN';
}
