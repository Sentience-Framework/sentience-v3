<?php

namespace Sentience\ORM\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Helpers\Arrays;
use Sentience\ORM\Database\Queries\Objects\ConditionGroup;
use Sentience\ORM\Database\Queries\Objects\RelationNode;
use Sentience\ORM\Database\Queries\Traits\RelationsTrait;
use Sentience\ORM\Models\Attributes\Relations\HasMany;
use Sentience\ORM\Models\Attributes\Relations\ManyToMany;
use Sentience\ORM\Models\Model;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class SelectModelsQuery extends ModelsQueryAbstract
{
    use DistinctTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use RelationsTrait;
    use WhereTrait;

    protected string|array|null $relationScopeColumn = null;
    protected array $relationScopeValues = [];

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(bool $emulatePrepare = false): array
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);

        $table = $reflectionModel->getTable();

        $relationNode = RelationNode::tree($reflectionModel, $this->relations);

        $columns = [];

        foreach ($reflectionModel->getProperties() as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isColumn()) {
                continue;
            }

            $column = $reflectionModelProperty->getColumn();

            $columns[$column] = [$table, $column];
        }

        $selectQuery = $this->database->select($table);

        $this->joinRelations($selectQuery, $table, $relationNode, $columns);

        $selectQuery->columns($columns);

        if ($this->distinct) {
            $selectQuery->distinct();
        }

        if ($this->relationScopeColumn) {
            $selectQuery->whereIn($this->relationScopeColumn, $this->relationScopeValues);
        }

        $selectQuery->whereGroup(fn (): ConditionGroup => (new ConditionGroup(ChainEnum::And, false))->addConditions($this->where));

        foreach ($this->orderBy as $orderBy) {
            $orderBy->direction == OrderByDirectionEnum::Asc
                ? $selectQuery->orderByAsc($orderBy->column)
                : $selectQuery->orderByDesc($orderBy->column);
        }

        if ($this->limit) {
            $selectQuery->limit($this->limit);
        }

        if ($this->offset) {
            $selectQuery->offset($this->offset);
        }

        $result = $selectQuery->execute($emulatePrepare);

        $models = [];

        foreach ($result->fetchAssocs() as $row) {
            $models[] = $this->mapAssocToRelations($model, $this->nestAssoc($row), $relationNode);
        }

        $this->loadRelations($models, $relationNode, $emulatePrepare);

        return $models;
    }

    protected function joinRelations(SelectQuery $selectQuery, string $alias, RelationNode $relationNode, array &$columns): void
    {
        foreach ($relationNode->getChildren() as $child) {
            if ($child->isToMany()) {
                continue;
            }

            $relation = $child->relation;

            $modelColumn = $relationNode->reflectionModel
                ->getProperty($relation->getModelProperty())
                ->getColumn();

            $relationColumn = $child->reflectionModel
                ->getProperty($relation->getRelationProperty())
                ->getColumn();

            $childAlias = $child->path;

            $selectQuery->leftJoinTable(
                $child->reflectionModel->getTable(),
                function (Join $join) use ($child, $childAlias, $relationColumn, $alias, $modelColumn): Join {
                    $join->on([$childAlias, $relationColumn], [$alias, $modelColumn]);

                    if ($child->callback) {
                        ($child->callback)($join);
                    }

                    return $join;
                },
                $childAlias
            );

            foreach ($child->reflectionModel->getProperties() as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isColumn()) {
                    continue;
                }

                $column = $reflectionModelProperty->getColumn();

                $columns[sprintf('%s%s%s', $childAlias, RelationNode::SEPARATOR, $column)] = [$childAlias, $column];
            }

            $this->joinRelations($selectQuery, $childAlias, $child, $columns);
        }
    }

    protected function nestAssoc(array $assoc): array
    {
        $nested = [];

        foreach ($assoc as $key => $value) {
            $properties = explode(RelationNode::SEPARATOR, $key);

            $column = array_pop($properties);

            $target = &$nested;

            foreach ($properties as $property) {
                if (!array_key_exists($property, $target)) {
                    $target[$property] = [];
                }

                $target = &$target[$property];
            }

            $target[$column] = $value;

            unset($target);
        }

        return $nested;
    }

    protected function mapAssocToRelations(string|Model $model, array $assoc, RelationNode $relationNode): Model
    {
        $model = $this->mapAssocToModel($model, $assoc);

        foreach ($relationNode->getChildren() as $child) {
            if ($child->isToMany()) {
                continue;
            }

            $childAssoc = $assoc[$child->property] ?? [];

            if ($this->isEmptyAssoc($child->reflectionModel, $childAssoc)) {
                $reflectionModelProperty = $relationNode->reflectionModel->getProperty($child->property);

                if ($reflectionModelProperty->allowsNull()) {
                    $model->{$child->property} = null;
                }

                continue;
            }

            $model->{$child->property} = $this->mapAssocToRelations($child->relation->model, $childAssoc, $child);
        }

        return $model;
    }

    protected function isEmptyAssoc(ReflectionModel $reflectionModel, array $assoc): bool
    {
        $primaryKeys = $reflectionModel->getPrimaryKeys();

        foreach ($assoc as $column => $value) {
            if (is_array($value)) {
                continue;
            }

            if (!Arrays::empty($primaryKeys) && !in_array($column, $primaryKeys)) {
                continue;
            }

            if (!is_null($value)) {
                return false;
            }
        }

        return true;
    }

    protected function loadRelations(array $models, RelationNode $relationNode, bool $emulatePrepare): void
    {
        if (Arrays::empty($models)) {
            return;
        }

        foreach ($relationNode->getChildren() as $child) {
            $relation = $child->relation;

            if (!$child->isToMany()) {
                $this->loadRelations(
                    $this->getRelationModels($models, $child->property),
                    $child,
                    $emulatePrepare
                );

                continue;
            }

            if ($relation instanceof ManyToMany) {
                $this->loadManyToMany($models, $child, $relation, $emulatePrepare);

                continue;
            }

            if ($relation instanceof HasMany) {
                $this->loadHasMany($models, $child, $relation, $emulatePrepare);
            }
        }
    }

    protected function getRelationModels(array $models, string $property): array
    {
        $relationModels = [];

        foreach ($models as $model) {
            $relationModel = $model->{$property} ?? null;

            if (!$relationModel) {
                continue;
            }

            $relationModels[] = $relationModel;
        }

        return $relationModels;
    }

    protected function getRelationKeys(array $models, string $property): array
    {
        $keys = [];

        foreach ($models as $model) {
            $key = $model->{$property} ?? null;

            if (is_null($key)) {
                continue;
            }

            $keys[] = $key;
        }

        return Arrays::unique($keys);
    }

    protected function loadHasMany(array $models, RelationNode $child, HasMany $relation, bool $emulatePrepare): void
    {
        $modelProperty = $relation->getModelProperty();
        $relationProperty = $relation->getRelationProperty();

        foreach ($models as $model) {
            $model->{$child->property} = [];
        }

        $keys = $this->getRelationKeys($models, $modelProperty);

        if (Arrays::empty($keys)) {
            return;
        }

        $relationColumn = $child->reflectionModel->getProperty($relationProperty)->getColumn();

        $relationModels = $this->selectRelationModels($child, $relationColumn, $keys, $emulatePrepare);

        $groupedRelationModels = [];

        foreach ($relationModels as $relationModel) {
            $groupedRelationModels[$relationModel->{$relationProperty}][] = $relationModel;
        }

        foreach ($models as $model) {
            $key = $model->{$modelProperty} ?? null;

            $model->{$child->property} = $groupedRelationModels[$key] ?? [];
        }
    }

    protected function loadManyToMany(array $models, RelationNode $child, ManyToMany $relation, bool $emulatePrepare): void
    {
        $modelProperty = $relation->getModelProperty();
        $relationProperty = $relation->getRelationProperty();

        foreach ($models as $model) {
            $model->{$child->property} = [];
        }

        $keys = $this->getRelationKeys($models, $modelProperty);

        if (Arrays::empty($keys)) {
            return;
        }

        $pivotReflectionModel = new ReflectionModel($relation->pivot);

        $pivotModelColumn = $pivotReflectionModel->getProperty($relation->getPivotModelProperty())->getColumn();
        $pivotRelationColumn = $pivotReflectionModel->getProperty($relation->getPivotRelationProperty())->getColumn();

        $pivots = $this->database->select($pivotReflectionModel->getTable())
            ->columns([
                $pivotModelColumn => $pivotModelColumn,
                $pivotRelationColumn => $pivotRelationColumn
            ])
            ->whereIn($pivotModelColumn, $keys)
            ->execute($emulatePrepare)
            ->fetchAssocs();

        if (Arrays::empty($pivots)) {
            return;
        }

        $relationColumn = $child->reflectionModel->getProperty($relationProperty)->getColumn();

        $relationModels = $this->selectRelationModels(
            $child,
            $relationColumn,
            Arrays::unique(array_column($pivots, $pivotRelationColumn)),
            $emulatePrepare
        );

        $indexedRelationModels = [];

        foreach ($relationModels as $relationModel) {
            $indexedRelationModels[$relationModel->{$relationProperty}] = $relationModel;
        }

        $groupedRelationModels = [];

        foreach ($pivots as $pivot) {
            $relationModel = $indexedRelationModels[$pivot[$pivotRelationColumn]] ?? null;

            if (!$relationModel) {
                continue;
            }

            $groupedRelationModels[$pivot[$pivotModelColumn]][] = $relationModel;
        }

        foreach ($models as $model) {
            $key = $model->{$modelProperty} ?? null;

            $model->{$child->property} = $groupedRelationModels[$key] ?? [];
        }
    }

    protected function selectRelationModels(RelationNode $relationNode, string $column, array $values, bool $emulatePrepare): array
    {
        $selectModelsQuery = new static($this->database, $this->dialect, $relationNode->relation->model);

        $selectModelsQuery->relations($relationNode->getPaths());
        $selectModelsQuery->scopeToRelation([$relationNode->reflectionModel->getTable(), $column], $values);

        if ($relationNode->callback) {
            ($relationNode->callback)($selectModelsQuery);
        }

        return $selectModelsQuery->execute($emulatePrepare);
    }

    protected function scopeToRelation(string|array $column, array $values): static
    {
        $this->relationScopeColumn = $column;
        $this->relationScopeValues = $values;

        return $this;
    }
}
