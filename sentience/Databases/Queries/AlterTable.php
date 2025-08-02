<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\AddColumn;
use sentience\Database\queries\objects\AddForeignKeyConstraint;
use sentience\Database\queries\objects\AddPrimaryKeys;
use sentience\Database\queries\objects\AddUniqueConstraint;
use sentience\Database\queries\objects\AlterColumn;
use sentience\Database\queries\objects\DropColumn;
use sentience\Database\queries\objects\DropConstraint;
use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\objects\RenameColumn;
use sentience\Database\queries\traits\Table;

class AlterTable extends Query
{
    use Table;

    protected array $alters = [];

    public function build(): QueryWithParams
    {
        return $this->dialect->alterTable([
            'table' => $this->table,
            'alters' => $this->alters
        ]);
    }

    public function addColumn(string $name, string $type, bool $notNull = false, mixed $defaultValue = null, bool $autoIncrement = false): static
    {
        $this->alters[] = new AddColumn($name, $type, $notNull, $defaultValue, $autoIncrement);

        return $this;
    }

    public function alterColumn(string $column, string $options): static
    {
        $this->alters[] = new AlterColumn($column, $options);

        return $this;
    }

    public function renameColumn(string $oldName, string $newName): static
    {
        $this->alters[] = new RenameColumn($oldName, $newName);

        return $this;
    }

    public function dropColumn(string $column): static
    {
        $this->alters[] = new DropColumn($column);

        return $this;
    }

    public function addPrimaryKeys(string|array $columns): static
    {
        $this->alters[] = new AddPrimaryKeys(is_string($columns) ? [$columns] : $columns);

        return $this;
    }

    public function addUniqueConstraint(array $columns, ?string $name): static
    {
        $this->alters[] = new AddUniqueConstraint($columns, $name);

        return $this;
    }

    public function addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): static
    {
        $this->alters[] = new AddForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name);

        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alters[] = new DropConstraint($constraint);

        return $this;
    }
}
