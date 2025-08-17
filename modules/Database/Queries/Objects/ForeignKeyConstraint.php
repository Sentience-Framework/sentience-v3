<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Objects;

class ForeignKeyConstraint
{
    public function __construct(public string $column, public string $referenceTable, public string $referenceColumn, public ?string $name = null)
    {
    }
}
