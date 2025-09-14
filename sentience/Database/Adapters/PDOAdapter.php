<?php

namespace Sentience\Database\Adapters;

use Closure;
use PDO;
use PDOException;
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
        protected DialectInterface $dialect,
        protected ?Closure $debug,
        protected array $options
    ) {
        $dsn = $driver == Driver::SQLITE
            ? sprintf(
                '%s:%s',
                $driver->value,
                $name
            )
            : sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $driver->value,
                $host,
                $port,
                $name
            );

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ]
        );

        if ($driver == Driver::SQLITE) {
            $this->configureForSQLite($options);
        }
    }

    protected function configureForSQLite(array $options): void
    {
        if (method_exists($this->pdo, 'sqliteCreateFunction')) {
            $this->pdo->sqliteCreateFunction(
                static::REGEXP_FUNCTION,
                fn (string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
                static::REGEXP_FUNCTION_ARGUMENTS_COUNT
            );
        }

        $this->query('PRAGMA journal_mode=WAL;');

        if ((bool) $options[SQLiteAdapter::OPTIONS_PRAGMA_SYNCHRONOUS_OFF] ?? false) {
            $this->query('PRAGMA synchronous=OFF');
        }
    }

    public function query(string $query): void
    {
        $startTime = microtime(true);

        $affected = $this->pdo->exec($query);

        if (is_bool($affected)) {
            $error = implode(' ', $this->pdo->errorInfo());

            ($this->debug)($query, $startTime, $error);

            throw new PDOException($error);
        }

        ($this->debug)($query, $startTime);
    }

    public function queryWithParams(QueryWithParams $queryWithParams): PDOResults
    {
        $rawQuery = $queryWithParams->toRawQuery($this->dialect);

        $startTime = microtime(true);

        $pdoStatement = $this->pdo->prepare($queryWithParams->query);

        if (is_bool($pdoStatement)) {
            $error = implode(' ', $this->pdo->errorInfo());

            ($this->debug)($rawQuery, $startTime, $error);

            throw new PDOException($error);
        }

        foreach ($queryWithParams->params as $index => $param) {
            $value = $this->dialect->castToDriver($param);

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

        $success = $pdoStatement->execute();

        if (!$success) {
            $error = implode(' ', $pdoStatement->errorInfo());

            ($this->debug)($rawQuery, $startTime, $error);

            throw new PDOException($error);
        }

        ($this->debug)($rawQuery, $startTime);

        return new PDOResults($pdoStatement);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        if (!$this->pdo->beginTransaction()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if (!$this->pdo->commit()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if (!$this->pdo->rollBack()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): string
    {
        $lastInserId = $this->pdo->lastInsertId($name);

        if (is_bool($lastInserId)) {
            return null;
        }

        return $lastInserId;
    }
}
