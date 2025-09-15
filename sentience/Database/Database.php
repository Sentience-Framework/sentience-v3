<?php

namespace Sentience\Database;

use Closure;
use Throwable;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\AlterModelQuery;
use Sentience\Database\Queries\AlterTableQuery;
use Sentience\Database\Queries\CreateModelQuery;
use Sentience\Database\Queries\CreateTableQuery;
use Sentience\Database\Queries\DeleteModelsQuery;
use Sentience\Database\Queries\DeleteQuery;
use Sentience\Database\Queries\DropModelQuery;
use Sentience\Database\Queries\DropTableQuery;
use Sentience\Database\Queries\InsertModelsQuery;
use Sentience\Database\Queries\InsertQuery;
use Sentience\Database\Queries\Objects\AliasObject;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Database\Queries\SelectModelsQuery;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\UpdateModelsQuery;
use Sentience\Database\Queries\UpdateQuery;
use Sentience\Database\Results\ResultsInterface;
use Sentience\Helpers\Arrays;
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
        protected ?Closure $debug,
        protected array $options,
        bool $usePDOAdapter = false
    ) {
        $adapter = $driver->getAdapter(
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug,
            $options,
            $usePDOAdapter
        );

        $dialect = $driver->getDialect();

        $this->adapter = $adapter;
        $this->dialect = $dialect;
    }

    public function query(string $query): void
    {
        $this->adapter->query($query);
    }

    public function prepared(string $query, array $params = []): ResultsInterface
    {
        return $this->queryWithParams(new QueryWithParamsObject($query, $params));
    }

    public function queryWithParams(QueryWithParamsObject $queryWithParams): ResultsInterface
    {
        return $this->adapter->queryWithParams($this->dialect, $queryWithParams);
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

    public function select(string|array|AliasObject|RawObject $table): SelectQuery
    {
        return new SelectQuery($this, $this->dialect, $table);
    }

    public function selectModels(string $model): SelectModelsQuery
    {
        return new SelectModelsQuery($this, $this->dialect, $model);
    }

    public function insert(string|array|AliasObject|RawObject $table = null): InsertQuery
    {
        return new InsertQuery($this, $this->dialect, $table);
    }

    public function insertModels(array|Model $models): InsertModelsQuery
    {
        return new InsertModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function update(string|array|AliasObject|RawObject $table = null): UpdateQuery
    {
        return new UpdateQuery($this, $this->dialect, $table);
    }

    public function updateModels(array|Model $models): UpdateModelsQuery
    {
        return new UpdateModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function delete(string|array|AliasObject|RawObject $table): DeleteQuery
    {
        return new DeleteQuery($this, $this->dialect, $table);
    }

    public function deleteModels(array|Model $models): DeleteModelsQuery
    {
        return new DeleteModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function createTable(string|array|AliasObject|RawObject $table): CreateTableQuery
    {
        return new CreateTableQuery($this, $this->dialect, $table);
    }

    public function createModel(string $model): CreateModelQuery
    {
        return new CreateModelQuery($this, $this->dialect, $model);
    }

    public function alterTable(string|array|AliasObject|RawObject $table): AlterTableQuery
    {
        return new AlterTableQuery($this, $this->dialect, $table);
    }

    public function alterModel(string $model): AlterModelQuery
    {
        return new AlterModelQuery($this, $this->dialect, $model);
    }

    public function dropTable(string|array|AliasObject|RawObject $table): DropTableQuery
    {
        return new DropTableQuery($this, $this->dialect, $table);
    }

    public function dropModel(string $model): DropModelQuery
    {
        return new DropModelQuery($this, $this->dialect, $model);
    }
}
