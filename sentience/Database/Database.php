<?php

namespace sentience\Database;

use Closure;
use PDO;
use PDOException;
use sentience\Abstracts\Singleton;
use sentience\Exceptions\DatabaseException;
use sentience\Helpers\Strings;
use sentience\Sentience\Sentience;
use Throwable;
use sentience\Database\dialects\DialectFactory;
use sentience\Database\dialects\DialectInterface;
use sentience\Database\queries\AlterTable;
use sentience\Database\queries\CreateTable;
use sentience\Database\queries\Delete;
use sentience\Database\queries\DropTable;
use sentience\Database\queries\Insert;
use sentience\Database\queries\objects\Alias;
use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\objects\Raw;
use sentience\Database\queries\Select;
use sentience\Database\queries\Update;

class Database extends Singleton
{
    protected string $dsn;
    protected ?Closure $debug;
    protected PDO $pdo;
    public DialectInterface $dialect;

    protected static function createInstance(): static
    {
        $debug = env('DB_DEBUG', false)
            ? function (string $query, float $startTime, ?string $error = null): void {
                $endTime = microtime(true);

                $lines = [
                    sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
                    sprintf('Query     : %s', $query),
                    sprintf('Time      : %.2f ms', ($endTime - $startTime) * 1000)
                ];

                if ($error) {
                    $lines[] = sprintf('Error     : %s', $error);
                }

                Sentience::logStderr('Query', $lines);
            }
            : null;

        $dsn = env('DB_DSN');

        if (!$dsn) {
            throw new DatabaseException('no DB_DSN defined in environment', $dsn);
        }

        return new static($dsn, $debug);
    }

    public function __construct(string $dsn, ?callable $debug = null, ?string $username = null, ?string $password = null)
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false
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
                    function (string $pattern, string $value): bool {
                        return preg_match(
                            sprintf(
                                '/%s/u',
                                Strings::escapeChars($pattern, ['/'])
                            ),
                            $value
                        );
                    },
                    2
                );
            }
        }

        $this->dialect = DialectFactory::fromDriver($driver);
    }

    public function unsafe(string $query): int
    {
        $startTime = microtime(true);

        $affected = $this->pdo->exec($query);

        if (is_bool($affected)) {
            $error = implode(' ', $this->pdo->errorInfo());

            if ($this->debug) {
                ($this->debug)($query, $startTime, $error);
            }

            throw new PDOException($error);
        }

        if ($this->debug) {
            ($this->debug)($query, $startTime);
        }

        return $affected;
    }

    public function safe(string $query, array $params = []): Results
    {
        $queryWithParams = new QueryWithParams($query, $params);

        $rawQuery = $queryWithParams->toRawQuery($this->dialect);

        $startTime = microtime(true);

        $pdoStatement = $this->pdo->prepare($query);

        if (is_bool($pdoStatement)) {
            $error = implode(' ', $this->pdo->errorInfo());

            if ($this->debug) {
                ($this->debug)($rawQuery, $startTime, $error);
            }

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

            if ($this->debug) {
                ($this->debug)($rawQuery, $startTime, $error);
            }

            throw new PDOException($error);
        }

        if ($this->debug) {
            ($this->debug)($rawQuery, $startTime);
        }

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

        if (!$lastInsertId) {
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
}
