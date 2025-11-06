<?php

namespace Sentience\Database;

use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;
use Throwable;

class Connection
{
    public const string LAZY = 'lazy';
    public const string PERSISTENT = 'persistent';

    public function __construct(
        protected AdapterInterface $adapter,
        protected DialectInterface $dialect
    ) {
    }

    public function exec(string $query): void
    {
        $this->adapter->exec($query);
    }

    public function query(string $query): ResultInterface
    {
        return $this->adapter->query($query);
    }

    public function prepared(string $query, array $params = [], bool $emulatePrepare = false): ResultInterface
    {
        return $this->queryWithParams(
            new QueryWithParams($query, $params),
            $emulatePrepare
        );
    }

    public function queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface
    {
        return count($queryWithParams->params) > 0
            ? $this->adapter->queryWithParams($this->dialect, $queryWithParams, $emulatePrepare)
            : $this->adapter->query($queryWithParams->query);
    }

    public function beginTransaction(): void
    {
        $this->adapter->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->adapter->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->adapter->rollbackTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->adapter->inTransaction();
    }

    public function transactionInCallback(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $return = $callback($this);

            $this->commitTransaction();

            return $return;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        return $this->adapter->lastInsertId($name);
    }
}
