<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Enums;

enum OrderByDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
