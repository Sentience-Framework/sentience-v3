<?php

namespace Modules\Database\Results;

use SQLite3Result;

class SQLiteResults implements ResultsInterface
{
    public function __construct(protected SQLite3Result $sqlite3Results)
    {
    }

    public function getColumns(): array
    {
        $columns = [];

        $index = 0;

        while (true) {
            $column = $this->sqlite3Results->columnName($index);

            if (!$column) {
                break;
            }

            $index++;

            $columns[] = $column;
        }

        return $columns;
    }

    public function nextRowAsObject(string $class = 'stdClass'): ?object
    {
        $assoc = $this->nextRowAsAssoc();

        if (is_null($assoc)) {
            return null;
        }

        $object = new $class();

        foreach ($assoc as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    public function allRowsAsObjects(string $class = 'stdClass'): array
    {
        $objects = [];

        while (true) {
            $object = $this->nextRowAsObject($class);

            if (is_null($object)) {
                break;
            }

            $objects[] = $object;
        }

        return $objects;
    }

    public function nextRowAsAssoc(): ?array
    {
        $assoc = $this->sqlite3Results->fetchArray(SQLITE3_ASSOC);

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function allRowsAsAssocs(): array
    {
        $assocs = [];

        while (true) {
            $assoc = $this->nextRowAsAssoc();

            if (is_null($assoc)) {
                break;
            }

            $assocs[] = $assoc;
        }

        return $assocs;
    }
}
