<?php

namespace Sentience\DataLayer\Database;

use Sentience\Database\Database as SentienceDatabase;
use Sentience\Helpers\Arrays;
use Sentience\DataLayer\Database\Queries\AlterModelQuery;
use Sentience\DataLayer\Database\Queries\CreateModelQuery;
use Sentience\DataLayer\Database\Queries\DeleteModelsQuery;
use Sentience\DataLayer\Database\Queries\DropModelQuery;
use Sentience\DataLayer\Database\Queries\InsertModelsQuery;
use Sentience\DataLayer\Database\Queries\SelectModelsQuery;
use Sentience\DataLayer\Database\Queries\UpdateModelsQuery;
use Sentience\DataLayer\Models\Model;

class Database extends SentienceDatabase
{
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
}
