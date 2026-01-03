<?php

namespace Sentience\ORM\Database\Queries;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\ORM\Database\Queries\Objects\ConditionGroup;
use Sentience\ORM\Database\Queries\Traits\RelationsTrait;
use Sentience\ORM\Models\Attributes\Relations\HasMany;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class SelectModelsQuery extends ModelsQueryAbstract
{
    use DistinctTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use RelationsTrait;
    use WhereTrait;

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

        $columns = [];

        foreach ($reflectionModel->getProperties() as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isColumn()) {
                continue;
            }

            $column = $reflectionModelProperty->getColumn();

            $columns[] = $column;
        }

        $selectQuery = $this->database->select($table)
            ->columns($columns);

        if ($this->distinct) {
            $selectQuery->distinct();
        }

        $selectQuery->whereGroup(
            fn (): ConditionGroup => new ConditionGroup(
                ChainEnum::AND,
                $this->where
            )
        );

        foreach ($this->orderBy as $orderBy) {
            $orderBy->direction == OrderByDirectionEnum::ASC
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

        return array_map(
            fn (array $row): object => $this->mapAssocToModel($model, $row),
            $result->fetchAssocs()
        );
    }

    public function executeNew(bool $emulatePrepare = false): array
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);

        $table = $reflectionModel->getTable();
        $alias = $reflectionModel->getShortName();

        $columns = [];

        foreach ($reflectionModel->getProperties() as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isColumn()) {
                continue;
            }

            $column = $reflectionModelProperty->getColumn();

            $columns[] = Query::alias(
                [$alias, $column],
                sprintf(
                    '%s->%s',
                    $alias,
                    $reflectionModelProperty->getProperty()
                )
            );
        }

        $selectQuery = $this->database->select(Query::alias($table, $alias));

        if ($this->distinct) {
            $selectQuery->distinct();
        }

        $selectQuery->whereGroup(fn (): ConditionGroup => new ConditionGroup(ChainEnum::AND, $this->where));

        foreach ($this->orderBy as $orderBy) {
            $orderBy->direction == OrderByDirectionEnum::ASC
                ? $selectQuery->orderByAsc($orderBy->column)
                : $selectQuery->orderByDesc($orderBy->column);
        }

        if ($this->limit) {
            $selectQuery->limit($this->limit);
        }

        if ($this->offset) {
            $selectQuery->offset($this->offset);
        }

        $this->addRelations($selectQuery, $reflectionModel, $columns);

        $selectQuery->columns($columns);

        $result = $selectQuery->execute($emulatePrepare);

        return array_map(
            fn (array $row): object => $this->mapAssocToModel($model, $row),
            $result->fetchAssocs()
        );
    }

    protected function addRelations(SelectQuery $selectQuery, ReflectionModel $reflectionModel, array &$columns): void
    {
        foreach ($this->relations as $relation) {
            $relationAttribute = $reflectionModel->getRelation($relation);

            if (!$relationAttribute) {
                continue;
            }

            match (true) {
                $relationAttribute instanceof HasMany => $this->addHasMany(
                    $selectQuery,
                    $reflectionModel,
                    $columns,
                    $relation,
                    $relationAttribute
                )
            };
        }
    }

    protected function addHasMany(SelectQuery $selectQuery, ReflectionModel $reflectionModel, array &$columns, string $relation, HasMany $hasMany): void
    {
        $relationReflectionModel = new ReflectionModel($hasMany->model);
        $relationTable = $relationReflectionModel->getTable();

        [$modelProperty, $relationModelProperty] = $hasMany->parseMToRJoin();

        $modelReflectionProperty = $reflectionModel->getProperty($modelProperty);
        $relationReflectionProperty = $relationReflectionModel->getProperty($relationModelProperty);

        foreach ($relationReflectionModel->getProperties() as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isColumn()) {
                continue;
            }

            $columns[] = Query::alias(
                [$relationTable, $reflectionModelProperty->getColumn()],
                sprintf(
                    '%s->%s',
                    $relation,
                    $reflectionModelProperty->getProperty()
                )
            );
        }

        // $selectQuery->leftJoin(
        //     $relationReflectionModel->getTable(),
        //     $relationReflectionProperty->getColumn(),
        //     $reflectionModel->getTable(),
        //     $modelReflectionProperty->getColumn()
        // );
    }
}
