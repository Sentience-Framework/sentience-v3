<?php

namespace Sentience\Database;

use Throwable;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Dialects\DialectInterface;
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
use Sentience\Database\Results\ResultsInterface;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Log;
use Sentience\Models\Model;

class Database
{
    protected AdapterInterface $adapter;
    protected DialectInterface $dialect;

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected array $queries,
        protected bool $debug,
        protected array $options
    ) {
        $adapter = $driver->getAdapter();
        $dialect = $driver->getDialect();

        $this->adapter = new $adapter(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug ? function (string $query, float $startTime, ?string $error = null): void {
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
            } : null,
            $options,
            $dialect,
        );

        $this->dialect = $dialect;
    }

    public function query(string $query): void
    {
        $this->adapter->query($query);
    }

    public function prepared(string $query, array $params = []): ResultsInterface
    {
        return $this->queryWithParams(new QueryWithParams($query, $params));
    }

    public function queryWithParams(QueryWithParams $queryWithParams): ResultsInterface
    {
        return $this->adapter->queryWithParams($queryWithParams);
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

    public function lastInsertId(?string $name = null): ?string
    {
        return $this->adapter->lastInsertId($name);
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
        return new InsertModels($this, $this->dialect, Arrays::wrap($models));
    }

    public function update(string|array|Alias|Raw $table = null): Update
    {
        return new Update($this, $this->dialect, $table);
    }

    public function updateModels(array|Model $models): UpdateModels
    {
        return new UpdateModels($this, $this->dialect, Arrays::wrap($models));
    }

    public function delete(string|array|Alias|Raw $table): Delete
    {
        return new Delete($this, $this->dialect, $table);
    }

    public function deleteModels(array|Model $models): DeleteModels
    {
        return new DeleteModels($this, $this->dialect, Arrays::wrap($models));
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
}
