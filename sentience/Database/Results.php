<?php

declare(strict_types=1);

namespace sentience\Database;

use PDO;
use PDOStatement;

class Results
{
    public function __construct(protected PDOStatement $pdoStatement, public string $query)
    {
    }

    public function countRows(): int
    {
        return $this->pdoStatement->rowCount();
    }

    public function countColumns(): int
    {
        return $this->pdoStatement->columnCount();
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

    public function fetch(string $class = 'stdClass'): ?object
    {
        $object = $this->pdoStatement->fetchObject($class);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchAll(string $class = 'stdClass'): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS, $class);
    }

    public function fetchAssociative(): ?array
    {
        $associative = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);

        if (is_bool($associative)) {
            return null;
        }

        return $associative;
    }

    public function fetchAllAssociative(): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId(?string $sequence = null): ?string
    {
        return Database::getInstance()->lastInsertId($sequence);
    }

    public function getPDOStatementAttribute(int $attribute): mixed
    {
        return $this->pdoStatement->getAttribute($attribute);
    }

    public function setPDOStatementAttribute(int $attribute, mixed $value): mixed
    {
        return $this->pdoStatement->setAttribute($attribute, $value);
    }
}
