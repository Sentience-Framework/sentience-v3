<?php

namespace Modules\Database\Results;

use PDO;
use PDOStatement;

class PDOResults implements ResultsInterface
{
    public function __construct(protected PDOStatement $pdoStatement)
    {
    }

    public function getColumns(): array
    {
        $columns = [];

        $index = 0;

        while (true) {
            $column = $this->pdoStatement->getColumnMeta($index);

            if (!$column) {
                break;
            }

            $index++;

            $columns[] = $column['name'];
        }

        return $columns;
    }

    public function nextRowAsObject(string $class = 'stdClass'): ?object
    {
        $object = $this->pdoStatement->fetchObject($class);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function allRowsAsObjects(string $class = 'stdClass'): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS, $class);
    }

    public function nextRowAsAssoc(): ?array
    {
        $associative = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);

        if (is_bool($associative)) {
            return null;
        }

        return $associative;
    }

    public function allRowsAsAssocs(): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }
}
