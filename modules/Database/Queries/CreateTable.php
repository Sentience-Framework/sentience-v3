<?php

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\Column;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Traits\Constraints;
use Modules\Database\Queries\Traits\IfNotExists;
use Modules\Database\Results;

class CreateTable extends ResultsQueryAbstract
{
    use Constraints;
    use IfNotExists;

    protected array $columns = [];
    protected array $primaryKeys = [];

    public function toQueryWithParams(): QueryWithParams
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

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): Results
    {
        return parent::execute();
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
