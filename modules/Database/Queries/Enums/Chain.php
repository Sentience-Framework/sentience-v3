<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Enums;

enum Chain: string
{
    case AND = 'AND';
    case OR = 'OR';
}
