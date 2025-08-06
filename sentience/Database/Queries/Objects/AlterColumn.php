<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

class AlterColumn
{
    public function __construct(public string $column, public string $options)
    {
    }
}
