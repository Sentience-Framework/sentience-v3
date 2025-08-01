<?php

namespace sentience\Database\queries\traits;

use sentience\Database\queries\objects\ForeignKeyConstraint;
use sentience\Database\queries\objects\UniqueConstraint;

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
