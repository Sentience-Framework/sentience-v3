<?php

declare(strict_types=1);

namespace Sentience\Database;

use PDO;
use PDOException;
use Throwable;
use Sentience\Abstracts\Singleton;
use Sentience\Database\Dialects\DialectFactory;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\DatabaseException;
use Sentience\Database\Queries\AlterModel;
use Sentience\Database\Queries\AlterTable;
use Sentience\Database\Queries\CreateModel;
use Sentience\Database\Queries\CreateTable;
use Sentience\Database\Queries\Delete;
use Sentience\Database\Queries\DeleteModels;
use Sentience\Database\Queries\DropModel;
use Sentience\Database\Queries\DropTable;
use Sentience\Database\Queries\Insert;
use Sentience\Database\Queries\InsertModels;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Select;
use Sentience\Database\Queries\SelectModels;
use Sentience\Database\Queries\Update;
use Sentience\Database\Queries\UpdateModels;
use Sentience\Helpers\Log;
use Sentience\Helpers\Strings;
use Sentience\Models\Model;

class Database extends Singleton
{
    protected static function createInstance(): static
    {
        $dsn = env('DB_DSN');
        $debug = env('DB_DEBUG');

        if (!$dsn) {
            throw new DatabaseException('no DB_DSN defined in environment', $dsn);
        }

        $pdo = new PDO(
            dsn: $dsn,
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ]
        );

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver == DialectFactory::PDO_DRIVER_SQLITE) {
            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction(
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

        $dialect = DialectFactory::fromPDODriver($driver);

        return new static($pdo, $debug, $dialect);
    }

    public function __construct(
        protected PDO $pdo,
        protected bool $debug,
        public DialectInterface $dialect
    ) {
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
        return $this->queryWithParams(new QueryWithParams($query, $params));
    }

    public function queryWithParams(QueryWithParams $queryWithParams): Results
    {
        $rawQuery = $queryWithParams->toRawQuery($this->dialect);

        $startTime = microtime(true);

        $pdoStatement = $this->pdo->prepare($queryWithParams->query);

        if (is_bool($pdoStatement)) {
            $error = implode(' ', $this->pdo->errorInfo());

            $this->debug($rawQuery, $startTime, $error);

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

    public function lastInsertId(?string $sequence = null): ?int
    {
        $lastInsertId = $this->pdo->lastInsertId($sequence);

        if (is_bool($lastInsertId)) {
            return null;
        }

        return (int) $lastInsertId;
    }

    public function getPDOAttribute(int $attribute): mixed
    {
        return $this->pdo->getAttribute($attribute);
    }

    public function setPDOAttribute(int $attribute, mixed $value): mixed
    {
        return $this->pdo->setAttribute($attribute, $value);
    }

    public function select(string|array|Alias|Raw $table): Select
    {
        return new Select($this, $this->dialect, $table);
    }

    public function selectModels(string $model): SelectModels
    {
        return new SelectModels($this, $this->dialect, $model);
    }

    public function insert(string|array|Alias|Raw $table = null): Insert
    {
        return new Insert($this, $this->dialect, $table);
    }

    public function insertModels(array|Model $models): InsertModels
    {
        return new InsertModels($this, $this->dialect, $models);
    }

    public function update(string|array|Alias|Raw $table = null): Update
    {
        return new Update($this, $this->dialect, $table);
    }

    public function updateModels(array|Model $models): UpdateModels
    {
        return new UpdateModels($this, $this->dialect, $models);
    }

    public function delete(string|array|Alias|Raw $table): Delete
    {
        return new Delete($this, $this->dialect, $table);
    }

    public function deleteModels(array|Model $models): DeleteModels
    {
        return new DeleteModels($this, $this->dialect, $models);
    }

    public function createTable(string|array|Alias|Raw $table): CreateTable
    {
        return new CreateTable($this, $this->dialect, $table);
    }

    public function createModel(string $model): CreateModel
    {
        return new CreateModel($this, $this->dialect, $model);
    }

    public function alterTable(string|array|Alias|Raw $table): AlterTable
    {
        return new AlterTable($this, $this->dialect, $table);
    }

    public function alterModel(string $model): AlterModel
    {
        return new AlterModel($this, $this->dialect, $model);
    }

    public function dropTable(string|array|Alias|Raw $table): DropTable
    {
        return new DropTable($this, $this->dialect, $table);
    }

    public function dropModel(string $model): DropModel
    {
        return new DropModel($this, $this->dialect, $model);
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

        Log::stderrBetweenEqualSigns('Query', $lines);
    }
}
