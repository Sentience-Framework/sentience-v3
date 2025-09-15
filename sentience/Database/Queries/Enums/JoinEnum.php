<?php

namespace Sentience\Database\Queries\Enums;

enum JoinEnum: string
{
    case LEFT_JOIN = 'LEFT JOIN';
    case RIGHT_JOIN = 'RIGHT JOIN';
    case INNER_JOIN = 'INNER JOIN';
}
