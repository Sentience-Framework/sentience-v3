<?php

namespace Sentience\Models\Database;

use Sentience\Database\Database;
use Sentience\Helpers\Arrays;
use Sentience\Models\Database\Queries\AlterModelQuery;
use Sentience\Models\Database\Queries\CreateModelQuery;
use Sentience\Models\Database\Queries\DeleteModelsQuery;
use Sentience\Models\Database\Queries\DropModelQuery;
use Sentience\Models\Database\Queries\InsertModelsQuery;
use Sentience\Models\Database\Queries\SelectModelsQuery;
use Sentience\Models\Database\Queries\UpdateModelsQuery;
use Sentience\Models\Model;

class DatabaseWithModels extends Database
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
