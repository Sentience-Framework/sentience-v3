<?php

namespace Modules\Database\Adapters;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Results\ResultsInterface;

interface AdapterInterface
{
    public function query(string $query): void;
    public function queryWithParams(QueryWithParams $queryWithParams): ResultsInterface;
    public function beginTransaction(): bool;
    public function inTransaction(): bool;
    public function commitTransaction(): bool;
    public function rollbackTransaction(): bool;
    public function lastInsertId(?string $name = null): string;
}
