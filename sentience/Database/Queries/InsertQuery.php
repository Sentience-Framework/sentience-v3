<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\ResultInterface;

class InsertQuery extends Query
{
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->insert(
            $this->table,
            $this->values,
            $this->onConflict,
            $this->returning
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->onConflict || $this->dialect::ON_CONFLICT) {
            return parent::execute($emulatePrepare);
        }

        if (is_string($this->onConflict->conflict)) {
            throw new QueryException('database only supports an array of columns as constraints');
        }

        $selectQuery = $this->database->select($this->table);

        $conflict = [];

        foreach ($this->onConflict->conflict as $column) {
            if (!array_key_exists($column, $this->values)) {
                continue;
            }

            $value = $this->values[$column];

            $conflict[$column] = $value;
        }

        foreach ($conflict as $column => $value) {
            $selectQuery->whereEquals($column, $value);
        }

        $selectQuery->limit(2);

        $count = $selectQuery->count(null, $emulatePrepare);

        if ($count == 0) {
            return parent::execute($emulatePrepare);
        }

        if ($count > 1) {
            throw new QueryException('multiple rows in constraint');
        }

        $updateQuery = $this->database->update($this->table);

        $onConflictIgnore = is_null($this->onConflict->updates);

        $updates = !$onConflictIgnore
            ? !empty($onConflict->updates) ? $this->onConflict->updates : $this->values
            : [];

        $updateQuery->values($updates);

        foreach ($this->onConflict->updates as $column => $value) {
            $updateQuery->whereEquals($column, $value);
        }

        if (!is_null($this->returning)) {
            $updateQuery->returning($this->returning);
        }

        return $updateQuery->execute($emulatePrepare);
    }
}
