<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
{
    public function connect(): void;
    public function disconnect(): void;
    public function reconnect(): void;
    public function isConnected(): bool;
    public function ping(DialectInterface $dialect, bool $reconnect = false): bool;
    public function enableLazy(bool $disconnect = true): void;
    public function disableLazy(bool $connect = true): void;
    public function isLazy(): bool;
    public function version(): int|string;
    public function exec(DialectInterface $dialect, string $query): void;
    public function query(DialectInterface $dialect, string $query): ResultInterface;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): ResultInterface;
    public function beginTransaction(DialectInterface $dialect): void;
    public function commitTransaction(DialectInterface $dialect): void;
    public function rollbackTransaction(DialectInterface $dialect): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): null|int|string;
}
