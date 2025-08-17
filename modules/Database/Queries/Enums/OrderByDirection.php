<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Enums;

enum OrderByDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
