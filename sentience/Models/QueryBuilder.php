<?php

namespace Sentience\Models;

use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\SelectModels;

class QueryBuilder
{
    protected string $model;
    protected array $relations = [];

    public function __construct(protected SelectModels $query, string|Model $model)
    {
        $this->model = is_object($model) ? $model::class : $model;
    }

    public function addRelation(string $relation): static
    {
        $this->relations[] = $relation;

        return $this;
    }

    public function addRelations(array $relations): static
    {
        array_push($this->relations, ...$relations);

        return $this;
    }

    public function buildQuery(): QueryWithParamsObject
    {
        return new QueryWithParamsObject('');
    }
}
