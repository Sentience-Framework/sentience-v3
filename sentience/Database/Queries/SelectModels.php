<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\Columns;
use Sentience\Database\Queries\Traits\Distinct;
use Sentience\Database\Queries\Traits\GroupBy;
use Sentience\Database\Queries\Traits\Having;
use Sentience\Database\Queries\Traits\Joins;
use Sentience\Database\Queries\Traits\Limit;
use Sentience\Database\Queries\Traits\Offset;
use Sentience\Database\Queries\Traits\OrderBy;
use Sentience\Database\Queries\Traits\Relations;
use Sentience\Database\Queries\Traits\Where;
use Sentience\Models\QueryBuilder;

class SelectModels extends ModelsQueryAbstract
{
    use Columns;
    use Distinct;
    use GroupBy;
    use Joins;
    use Having;
    use Limit;
    use Offset;
    use OrderBy;
    use Relations;
    use Where;

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
            fn(array $row): object => $this->mapAssocToModel($model, $row),
            $results->allRowsAsAssocs()
        );
    }
}
