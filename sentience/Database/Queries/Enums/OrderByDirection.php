<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Enums;

enum OrderByDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
