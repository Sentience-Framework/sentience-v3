<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Enums;

enum Chain: string
{
    case AND = 'AND';
    case OR = 'OR';
}
