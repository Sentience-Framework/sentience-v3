<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ConstraintsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Results\ResultInterface;

class CreateTableQuery extends ResultQueryAbstract
{
    use ConstraintsTrait;
    use IfNotExistsTrait;

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
                'foreignKeys' => $this->foreignKeyConstraints
            ]
        ]);
    }

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultInterface
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
