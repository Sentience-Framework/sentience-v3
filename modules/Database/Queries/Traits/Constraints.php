<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

use Modules\Database\Queries\Objects\ForeignKeyConstraint;
use Modules\Database\Queries\Objects\UniqueConstraint;

trait Constraints
{
    protected array $uniqueConstraints = [];
    protected array $foreignKeyConstraints = [];

    public function uniqueConstraint(array $columns, ?string $name = null): static
    {
        $this->uniqueConstraints[] = new UniqueConstraint($columns, $name);

        return $this;
    }

    public function foreignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): static
    {
        $this->foreignKeyConstraints[] = new ForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name);

        return $this;
    }
}
