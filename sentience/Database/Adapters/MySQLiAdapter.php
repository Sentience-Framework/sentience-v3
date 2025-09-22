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

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->mysqli = new mysqli(
            $host,
            $username,
            $password,
            $name,
            $port
        );

        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
            $this->mysqli->set_charset((string) $options['charset']);
        }

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    public function query(string $query): void
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

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): MySQLiResult
    {
        $query = $queryWithParams->toRawQuery($dialect);

        $start = microtime(true);

        try {
            $mysqliStatement = $this->mysqli->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $paramTypes = [];
        $paramValues = [];

        foreach ($queryWithParams->params as $param) {
            $value = $dialect->castToDriver($param);

            $paramTypes[] = match (get_debug_type($value)) {
                'null' => 's',
                'int' => 'i',
                'float' => 'd',
                'string' => 's',
                default => 's'
            };

            $paramValues[] = $value;
        }

        if (count($paramValues) > 0) {
            $mysqliStatement->bind_param(
                implode(
                    '',
                    $paramTypes
                ),
                ...$paramValues
            );
        }

        try {
            $mysqliStatement->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $result = $mysqliStatement->get_result();

        $this->debug($query, $start);

        return new MySQLiResult($result);
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

        $this->mysqli->commit();

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->mysqli->rollback();

        $this->inTransaction = false;
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
