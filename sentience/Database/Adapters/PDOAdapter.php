<?php

namespace Sentience\Database\Adapters;

use Closure;
use PDO;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\PDOResults;

class PDOAdapter extends AdapterAbstract
{
    protected PDO $pdo;

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected array $queries,
        protected ?Closure $debug,
        protected array $options
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

        $dsn = $this->dsn(
            $driver,
            $host,
            $port,
            $name,
            $options
        );

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_PERSISTENT => true
            ]
        );

        if ($driver == Driver::SQLITE) {
            $this->configurePDOForSQLite($options);
        }

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    protected function dsn(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        array $options
    ): string {
        if ($driver == Driver::SQLITE) {
            return sprintf(
                '%s:%s',
                $driver->value,
                $name
            );
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $driver->value,
            $host,
            $port,
            $name
        );

        if ($driver == Driver::MYSQL) {
            if (array_key_exists(static::OPTIONS_CHARSET, $options)) {
                $dsn .= sprintf(';charset=%s' . (string) $options['charset']);
            }
        }

        return $dsn;
    }

    protected function configurePDOForSQLite(array $options): void
    {
        if (method_exists($this->pdo, 'sqliteCreateFunction')) {
            $this->pdo->sqliteCreateFunction(
                static::REGEXP_FUNCTION,
                fn (string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
                static::REGEXP_FUNCTION_ARGUMENTS_COUNT
            );
        }
    }

    public function query(string $query): void
    {
        $start = microtime(true);

        try {
            $this->pdo->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): PDOResults
    {
        $query = $queryWithParams->toRawQuery($dialect);

        $start = microtime(true);

        try {
            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        foreach ($queryWithParams->params as $index => $param) {
            $value = $dialect->castToDriver($param);

            $pdoStatement->bindValue(
                $index + 1,
                $value,
                match (get_debug_type($value)) {
                    'null' => PDO::PARAM_NULL,
                    'bool' => PDO::PARAM_BOOL,
                    'int' => PDO::PARAM_INT,
                    'float' => PDO::PARAM_STR,
                    'string' => PDO::PARAM_STR,
                    default => PDO::PARAM_STR
                }
            );
        }

        try {
            $pdoStatement->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);

        return new PDOResults($pdoStatement);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->commit();
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): ?string
    {
        $lastInserId = $this->pdo->lastInsertId($name);

        if (is_bool($lastInserId)) {
            return null;
        }

        return $lastInserId;
    }
}
