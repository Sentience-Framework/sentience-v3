<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\LastInsertIdTrait;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\ResultInterface;

class InsertQuery extends Query
{
    use LastInsertIdTrait;
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->insert(
            $this->table,
            $this->values,
            $this->onConflict,
            $this->returning,
            $this->lastInsertId
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->onConflict || $this->dialect->onConflict()) {
            return $this->insertLastInsertId($emulatePrepare);
        }

        if (is_string($this->onConflict->conflict)) {
            throw new QueryException('database only supports an array of columns as constraints');
        }

        $conflict = [];

        foreach ($this->onConflict->conflict as $column) {
            if (!array_key_exists($column, $this->values)) {
                continue;
            }

            $value = $this->values[$column];

            $conflict[$column] = $value;
        }

        $selectQuery = $this->database->select($this->table);

        foreach ($conflict as $column => $value) {
            $selectQuery->whereEquals($column, $value);
        }

        $selectQuery->limit(2);

        $count = $selectQuery->count(null, $emulatePrepare);

        if ($count == 0) {
            return $this->insertLastInsertId($emulatePrepare);
        }

        if ($count > 1) {
            throw new QueryException('multiple rows in constraint');
        }

        return $this->update($conflict, $emulatePrepare);
    }

    protected function insertLastInsertId(bool $emulatePrepare): ResultInterface
    {
        $result = parent::execute($emulatePrepare);

        if (!$this->lastInsertId || is_null($this->returning) || $this->dialect->returning()) {
            return $result;
        }

        $lastInsertId = $this->database->lastInsertId();

        if (empty($lastInsertId)) {
            return $result;
        }

        return $this->database->select($this->table)
            ->columns(
                array_unique(
                    count($this->returning) > 0
                    ? [$this->lastInsertId, ...$this->returning]
                    : []
                )
            )
            ->whereEquals($this->lastInsertId, $lastInsertId)
            ->limit(1)
            ->execute($emulatePrepare);
    }

    protected function update(array $conflict, bool $emulatePrepare): ResultInterface
    {
        $updateQuery = $this->database->update($this->table);

        $updates = is_null($this->onConflict->updates)
            ? !empty($onConflict->updates) ? $this->onConflict->updates : $this->values
            : [];

        $updateQuery->values($updates);

        foreach ($conflict as $column => $value) {
            $updateQuery->whereEquals($column, $value);
        }

        if (!is_null($this->returning)) {
            $updateQuery->returning($this->returning);
        }

        return $updateQuery->execute($emulatePrepare);
    }
}
