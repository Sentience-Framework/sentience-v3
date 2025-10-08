<?php

namespace Sentience\Database\Adapters;

use Closure;
use mysqli;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\MySQLiResult;

class MySQLiAdapter extends AdapterAbstract
{
    protected mysqli $mysqli;
    protected bool $inTransaction = false;

    public function __construct(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        parent::__construct(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug
        );

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->mysqli = new mysqli(
            $host,
            $username,
            $password,
            $name,
            $port
        );

        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
            $this->mysqlNames(
                (string) $options[static::OPTIONS_MYSQL_CHARSET],
                $options[static::OPTIONS_MYSQL_COLLATION] ?? null
            );
        }

        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $options)) {
            $this->mysqlEngine((string) $options[static::OPTIONS_MYSQL_ENGINE]);
        }

        foreach ($queries as $query) {
            $this->exec($query);
        }
    }

    public function exec(string $query): void
    {
        $start = microtime(true);

        try {
            $this->mysqli->query($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function query(string $query): MySQLiResult
    {
        try {
            $start = microtime(true);

            $mysqliResult = $this->mysqli->query($query);

            $this->debug($query, $start);

            return new MySQLiResult($mysqliResult);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): MySQLiResult
    {
        $queryWithParams->namedParamsToQuestionMarks();

        $query = $queryWithParams->toSql($dialect);

        if ($emulatePrepare) {
            return $this->query($query);
        }

        $start = microtime(true);

        try {
            $mysqliStmt = $this->mysqli->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $paramTypes = [];
        $paramValues = [];

        foreach ($queryWithParams->params as $param) {
            $value = $dialect->castToDriver($param);

            $type = match (get_debug_type($value)) {
                'null' => 's',
                'int' => 'i',
                'float' => 'd',
                'string' => 's',
                default => 's'
            };

            $paramTypes[] = $type;
            $paramValues[] = $value;
        }

        if (count($paramValues) > 0) {
            $mysqliStmt->bind_param(
                implode(
                    '',
                    $paramTypes
                ),
                ...$paramValues
            );
        }

        try {
            $mysqliStmt->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $mysqliResult = $mysqliStmt->get_result();

        $this->debug($query, $start);

        return new MySQLiResult($mysqliResult);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->mysqli->begin_transaction();

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->commit();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->rollback();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string) $this->mysqli->insert_id;
    }
}
