<?php

namespace Sentience\ORM\Database;

use Closure;
use Sentience\Database\Database;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;
use Sentience\Helpers\Arrays;
use Sentience\ORM\Database\Queries\AlterModelQuery;
use Sentience\ORM\Database\Queries\CreateModelQuery;
use Sentience\ORM\Database\Queries\DeleteModelsQuery;
use Sentience\ORM\Database\Queries\DropModelQuery;
use Sentience\ORM\Database\Queries\InsertModelsQuery;
use Sentience\ORM\Database\Queries\SelectModelsQuery;
use Sentience\ORM\Database\Queries\UpdateModelsQuery;
use Sentience\ORM\Database\Results\CachedResult;
use Sentience\ORM\Models\Model;

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

    public function selectModels(string $model): SelectModelsQuery
    {
        return new SelectModelsQuery($this, $this->dialect, $model, $this->whereMacros);
    }

    public function insertModels(array|Model $models): InsertModelsQuery
    {
        return new InsertModelsQuery($this, $this->dialect, Arrays::wrap($models));
    }

    public function updateModels(array|Model $models): UpdateModelsQuery
    {
        return new UpdateModelsQuery($this, $this->dialect, Arrays::wrap($models), $this->whereMacros);
    }

    public function deleteModels(array|Model $models): DeleteModelsQuery
    {
        return new DeleteModelsQuery($this, $this->dialect, Arrays::wrap($models), $this->whereMacros);
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

    protected function storeCache(string|QueryWithParams $query, ResultInterface $result): CachedResult
    {
        $cachedResult = CachedResult::fromInterface($result);

        ($this->storeCache)($query instanceof QueryWithParams ? $query->toSql($this->dialect) : $query, $cachedResult);

        return $cachedResult;
    }

    protected function retrieveCache(string|QueryWithParams $query): ?CachedResult
    {
        return ($this->retrieveCache)($query instanceof QueryWithParams ? $query->toSql($this->dialect) : $query);
    }
}
