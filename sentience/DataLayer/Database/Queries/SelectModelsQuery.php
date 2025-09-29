<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\DataLayer\Database\Queries\Traits\RelationsTrait;
use Sentience\DataLayer\Models\Attributes\Relations\HasMany;
use Sentience\DataLayer\Models\Reflection\ReflectionModel;

class SelectModelsQuery extends ModelsQueryAbstract
{
    use DistinctTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use RelationsTrait;
    use WhereTrait;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(): array
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);

        $table = $reflectionModel->getTable();
        $alias = $reflectionModel->getShortName();
        $columns = $reflectionModel->getColumns();

        // $columns = [];

        // foreach ($reflectionModel->getProperties() as $reflectionModelProperty) {
        //     if (!$reflectionModelProperty->isColumn()) {
        //         continue;
        //     }

        //     $columns[] = Query::alias(
        //         [$alias, $reflectionModelProperty->getColumn()],
        //         sprintf(
        //             '%s->%s',
        //             $alias,
        //             $reflectionModelProperty->getProperty()
        //         )
        //     );
        // }

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

        // $this->addRelations($selectQuery, $reflectionModel, $columns);

        $selectQuery->columns($columns);

        $result = $selectQuery->execute();

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

        $selectQuery->leftJoin(
            $relationReflectionModel->getTable(),
            $relationReflectionProperty->getColumn(),
            $reflectionModel->getTable(),
            $modelReflectionProperty->getColumn()
        );
    }
}
