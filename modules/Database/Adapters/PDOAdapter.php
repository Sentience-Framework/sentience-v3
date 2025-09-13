<?php

namespace Modules\Database\Adapters;

use Closure;
use PDO;
use PDOException;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Driver;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Results\PDOResults;

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

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function commitTransaction(): bool
    {
        if (!$this->inTransaction()) {
            return false;
        }

        if (!$this->pdo->commit()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }

        return true;
    }

    public function rollbackTransaction(): bool
    {
        if (!$this->inTransaction()) {
            return false;
        }

        if (!$this->pdo->rollBack()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }

        return true;
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
