<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\GroupByTrait;
use Sentience\Database\Queries\Traits\HavingTrait;
use Sentience\Database\Queries\Traits\JoinsTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\RelationsTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Models\QueryBuilder;

class SelectModels extends ModelsQueryAbstract
{
    use ColumnsTrait;
    use DistinctTrait;
    use GroupByTrait;
    use JoinsTrait;
    use HavingTrait;
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

        // $queryBuilder = new QueryBuilder($model);
        // $joins = $queryBuilder->addRelations($this->relations);
        // $queryBuilder->buildQuery();

        $queryWithParams = $this->dialect->select([
            'distinct' => $this->distinct,
            'columns' => $this->columns,
            'table' => $model::getTable(),
            'joins' => $this->joins,
            'where' => $this->where,
            'groupBy' => $this->groupBy,
            'having' => $this->having,
            'orderBy' => $this->orderBy,
            'limit' => $this->limit,
            'offset' => $this->offset
        ]);

        $results = $this->executeQueryWithParams($queryWithParams);

        return array_map(
            fn (array $row): object => $this->mapAssocToModel($model, $row),
            $results->fetchAssocs()
        );
    }
}
