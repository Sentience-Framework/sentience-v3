<?php

namespace Sentience\Database\Adapters;

use Closure;
use SQLite3;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\SQLite3Result;

class SQLite3Adapter extends AdapterAbstract
{
    public const OPTIONS_READ_ONLY = 'sqlite3_read_only';
    public const OPTIONS_ENCRYPTION_KEY = 'sqlite3_encryption_key';
    public const OPTIONS_BUSY_TIMEOUT = 'sqlite3_busy_timeout';

    protected SQLite3 $sqlite;
    protected bool $inTransaction = false;

    public function __construct(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        ?Closure $debug,
        array $options
    ) {
        parent::__construct(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug,
            $options
        );

        $this->sqlite = new SQLite3(
            $name,
            ($options[static::OPTIONS_READ_ONLY] ?? false)
            ? SQLITE3_OPEN_READONLY | SQLITE3_OPEN_CREATE
            : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
            $options[static::OPTIONS_ENCRYPTION_KEY] ?? ''
        );

        $this->sqlite->createFunction(
            static::REGEXP_FUNCTION,
            fn (string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
            static::REGEXP_FUNCTION_ARGUMENTS_COUNT
        );

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    public function query(string $query): void
    {
        $start = microtime(true);

        try {
            $this->sqlite->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): SQLite3Result
    {
        $query = $queryWithParams->toRawQuery($dialect);

        $start = microtime(true);

        try {
            $sqlite3Statement = $this->sqlite->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        foreach ($queryWithParams->params as $index => $param) {
            $value = $dialect->castToDriver($param);

            $sqlite3Statement->bindValue(
                $index + 1,
                $value,
                match (get_debug_type($value)) {
                    'null' => SQLITE3_NULL,
                    'int' => SQLITE3_INTEGER,
                    'float' => SQLITE3_FLOAT,
                    'string' => SQLITE3_TEXT,
                    default => SQLITE3_TEXT
                }
            );
        }

        try {
            $sqlite3Result = $sqlite3Statement->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);

        return new SQLite3Result($sqlite3Result);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->query('BEGIN;');

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('COMMIT;');

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('ROLLBACK;');

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string) $this->sqlite->lastInsertRowID();
    }
}
