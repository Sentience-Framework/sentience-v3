<?php

declare(strict_types=1);

namespace sentience\Database;

use Closure;
use PDO;
use PDOException;
use Throwable;
use sentience\Abstracts\Singleton;
use sentience\Database\Dialects\DialectFactory;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\AlterTable;
use sentience\Database\Queries\CreateTable;
use sentience\Database\Queries\Delete;
use sentience\Database\Queries\DropTable;
use sentience\Database\Queries\Insert;
use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Select;
use sentience\Database\Queries\Update;
use sentience\Exceptions\DatabaseException;
use sentience\Helpers\Strings;
use sentience\Sentience\Sentience;

class Database extends Singleton
{
    protected string $dsn;
    protected bool $debug;
    protected PDO $pdo;
    public DialectInterface $dialect;

    protected static function createInstance(): static
    {
        $dsn = env('DB_DSN');
        $debug = env('DB_DEBUG');

        if (!$dsn) {
            throw new DatabaseException('no DB_DSN defined in environment', $dsn);
        }

        return new static($dsn, $debug);
    }

    public function __construct(string $dsn, bool $debug, ?string $username = null, ?string $password = null, array $options = [])
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            ...$options
        ];

        $this->debug = $debug;

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            $options
        );

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver == DialectFactory::PDO_DRIVER_SQLITE) {
            if (method_exists($this->pdo, 'sqliteCreateFunction')) {
                $this->pdo->sqliteCreateFunction(
                    'REGEXP',
                    fn(string $pattern, string $value): bool => preg_match(
                        sprintf(
                            '/%s/u',
                            Strings::escapeChars($pattern, ['/'])
                        ),
                        $value
                    ),
                    2
                );
            }
        }

        $this->dialect = DialectFactory::fromPDODriver($driver);
    }

    public function exec(string $query): int
    {
        $startTime = microtime(true);

        $affected = $this->pdo->exec($query);

        if (is_bool($affected)) {
            $error = implode(' ', $this->pdo->errorInfo());

            $this->debug($query, $startTime, $error);

            throw new PDOException($error);
        }

        $this->debug($query, $startTime);

        return $affected;
    }

    public function prepared(string $query, array $params = []): Results
    {
        $queryWithParams = new QueryWithParams($query, $params);

        $rawQuery = $queryWithParams->toRawQuery($this->dialect);

        $startTime = microtime(true);

        $pdoStatement = $this->pdo->prepare($query);

        if (is_bool($pdoStatement)) {
            $error = implode(' ', $this->pdo->errorInfo());

            $this->debug($rawQuery, $startTime, $error);

            throw new PDOException($error);
        }

        foreach ($params as $index => $param) {
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

            $this->debug($rawQuery, $startTime, $error);

            throw new PDOException($error);
        }

        $this->debug($rawQuery, $startTime);

        return new Results($pdoStatement, $rawQuery);
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->beginTransaction()) {
            throw new PDOException(implode(' ', $this->pdo->errorInfo()));
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
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

    public function transactionInCallback(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();

            return $result;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function lastInsertId(?string $sequence = null): ?string
    {
        $lastInsertId = $this->pdo->lastInsertId($sequence);

        if (is_bool($lastInsertId)) {
            return null;
        }

        return $lastInsertId;
    }

    public function getPDOAttribute(int $attribute): mixed
    {
        return $this->pdo->getAttribute($attribute);
    }

    public function setPDOAttribute(int $attribute, mixed $value): mixed
    {
        return $this->pdo->setAttribute($attribute, $value);
    }

    public function select(null|string|array|Alias|Raw $table = null): Select
    {
        $query = new Select($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function insert(null|string|array|Alias|Raw $table = null): Insert
    {
        $query = new Insert($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function update(null|string|array|Alias|Raw $table = null): Update
    {
        $query = new Update($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function delete(null|string|array|Alias|Raw $table = null): Delete
    {
        $query = new Delete($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function createTable(null|string|array|Alias|Raw $table = null): CreateTable
    {
        $query = new CreateTable($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function alterTable(null|string|array|Alias|Raw $table = null): AlterTable
    {
        $query = new AlterTable($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    public function dropTable(null|string|array|Alias|Raw $table = null): DropTable
    {
        $query = new DropTable($this, $this->dialect);

        if ($table) {
            $query->table($table);
        }

        return $query;
    }

    protected function debug(string $query, float $startTime, ?string $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        $endTime = microtime(true);

        $lines = [
            sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
            sprintf('Query     : %s', $query),
            sprintf('Time      : %.2f ms', ($endTime - $startTime) * 1000)
        ];

        if ($error) {
            $lines[] = sprintf('Error     : %s', $error);
        }

        Sentience::log('Query', $lines);
    }
}
