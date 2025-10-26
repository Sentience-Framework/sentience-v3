<?php

namespace Sentience\DataLayer\Database;

use Closure;
use Sentience\Database\Database;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Results\ResultInterface;
use Sentience\DataLayer\Database\Queries\AlterModelQuery;
use Sentience\DataLayer\Database\Queries\CreateModelQuery;
use Sentience\DataLayer\Database\Queries\CreateTableQuery;
use Sentience\DataLayer\Database\Queries\DeleteModelsQuery;
use Sentience\DataLayer\Database\Queries\DropModelQuery;
use Sentience\DataLayer\Database\Queries\InsertModelsQuery;
use Sentience\DataLayer\Database\Queries\SelectModelsQuery;
use Sentience\DataLayer\Database\Queries\UpdateModelsQuery;
use Sentience\DataLayer\Models\Model;
use Sentience\Helpers\Arrays;

class DB extends Database
{
    protected bool $cache = false;
    protected ?Closure $storeCache = null;
    protected ?Closure $retrieveCache = null;

    public function query(string $query): ResultInterface
    {
        if (!$this->cache) {
            return parent::query($query);
        }

        $cache = $this->retrieveCache($query);

        if (!$cache) {
            return $this->storeCache(
                $query,
                parent::query($query)
            );
        }

        return $cache;
    }

    public function queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->cache) {
            return parent::queryWithParams($queryWithParams, $emulatePrepare);
        }

        $cache = $this->retrieveCache($queryWithParams);

        if (!$cache) {
            return $this->storeCache(
                $queryWithParams,
                parent::queryWithParams($queryWithParams, $emulatePrepare)
            );
        }

        return $cache;
    }

    public function createTable(array|string|Alias|Raw $table): CreateTableQuery
    {
        return new CreateTableQuery($this, $this->dialect, $table);
    }

    public function selectModels(string $model): SelectModelsQuery
    {
        return new SelectModelsQuery($this, $this->dialect, $model);
    }

    public function insertModels(array|Model $models): InsertModelsQuery
    {
        return new InsertModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function updateModels(array|Model $models): UpdateModelsQuery
    {
        return new UpdateModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function deleteModels(array|Model $models): DeleteModelsQuery
    {
        return new DeleteModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function createModel(string $model): CreateModelQuery
    {
        return new CreateModelQuery($this, $this->dialect, $model);
    }

    public function alterModel(string $model): AlterModelQuery
    {
        return new AlterModelQuery($this, $this->dialect, $model);
    }

    public function dropModel(string $model): DropModelQuery
    {
        return new DropModelQuery($this, $this->dialect, $model);
    }

    public function cache(callable $store, callable $retrieve): static
    {
        $this->cache = true;
        $this->storeCache = Closure::fromCallable($store);
        $this->retrieveCache = Closure::fromCallable($retrieve);

        return $this;
    }

    protected function storeCache(string|QueryWithParams $query, ResultInterface $result): ResultInterface
    {
        if (!$this->storeCache) {
            return $result;
        }

        ($this->storeCache)($query instanceof QueryWithParams ? $query->toSql($this->dialect) : $query, $result);

        return $result;
    }

    protected function retrieveCache(string|QueryWithParams $query): ?ResultInterface
    {
        if (!$this->retrieveCache) {
            return null;
        }

        return ($this->retrieveCache)($query instanceof QueryWithParams ? $query->toSql($this->dialect) : $query);
    }
}
