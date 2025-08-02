<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\Column;
use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Traits\Constraints;
use sentience\Database\Queries\Traits\IfNotExists;
use sentience\Database\Queries\Traits\Table;

class CreateTable extends Query
{
    use Constraints;
    use IfNotExists;
    use Table;

    protected array $columns = [];
    protected array $primaryKeys = [];

    public function build(): QueryWithParams
    {
        return $this->dialect->createTable([
            'ifNotExists' => $this->ifNotExists,
            'table' => $this->table,
            'columns' => $this->columns,
            'primaryKeys' => $this->primaryKeys,
            'constraints' => [
                'unique' => $this->uniqueConstraints,
                'foreignKey' => $this->foreignKeyConstraints
            ]
        ]);
    }

    public function column(string $name, string $type, bool $notNull = false, mixed $defaultValue = null, bool $autoIncrement = false): static
    {
        $this->columns[] = new Column($name, $type, $notNull, $defaultValue, $autoIncrement);

        return $this;
    }

    public function primaryKeys(string|array $keys): static
    {
        $this->primaryKeys = is_string($keys)
            ? [$keys]
            : $keys;

        return $this;
    }
}
