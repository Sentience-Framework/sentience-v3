<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Enums;

enum Chain: string
{
    case AND = 'AND';
    case OR = 'OR';
}
