<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Enums;

enum JoinType: string
{
    case LEFT_JOIN = 'LEFT JOIN';
    case RIGHT_JOIN = 'RIGHT JOIN';
    case INNER_JOIN = 'INNER JOIN';
}
