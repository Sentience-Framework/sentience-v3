<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\AddColumn;
use Modules\Database\Queries\Objects\AddForeignKeyConstraint;
use Modules\Database\Queries\Objects\AddPrimaryKeys;
use Modules\Database\Queries\Objects\AddUniqueConstraint;
use Modules\Database\Queries\Objects\AlterColumn;
use Modules\Database\Queries\Objects\DropColumn;
use Modules\Database\Queries\Objects\DropConstraint;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Objects\RenameColumn;
use Modules\Database\Results;

class AlterTable extends ResultsQueryAbstract
{
    protected array $alters = [];

    public function toQueryWithParams(): array
    {
        return $this->dialect->alterTable([
            'table' => $this->table,
            'alters' => $this->alters
        ]);
    }

    public function toRawQuery(): array
    {
        $queriesWithParams = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): string => $queryWithParams->toRawQuery($this->dialect),
            $queriesWithParams
        );
    }

    public function execute(): array
    {
        $queries = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): Results => $this->database->queryWithParams($queryWithParams),
            $queries
        );
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
