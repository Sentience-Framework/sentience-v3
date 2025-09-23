<?php

namespace Sentience\ORM\Database;

use Sentience\Database\Database as SentienceDatabase;
use Sentience\Helpers\Arrays;
use Sentience\ORM\Database\Queries\AlterModelQuery;
use Sentience\ORM\Database\Queries\CreateModelQuery;
use Sentience\ORM\Database\Queries\DeleteModelsQuery;
use Sentience\ORM\Database\Queries\DropModelQuery;
use Sentience\ORM\Database\Queries\InsertModelsQuery;
use Sentience\ORM\Database\Queries\SelectModelsQuery;
use Sentience\ORM\Database\Queries\UpdateModelsQuery;
use Sentience\ORM\Models\Model;

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
