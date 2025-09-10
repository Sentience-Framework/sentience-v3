<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Traits\Columns;
use Modules\Database\Queries\Traits\Distinct;
use Modules\Database\Queries\Traits\GroupBy;
use Modules\Database\Queries\Traits\Having;
use Modules\Database\Queries\Traits\Joins;
use Modules\Database\Queries\Traits\Limit;
use Modules\Database\Queries\Traits\Offset;
use Modules\Database\Queries\Traits\OrderBy;
use Modules\Database\Queries\Traits\Relations;
use Modules\Database\Queries\Traits\Where;
use Modules\Models\Mapper;
use Modules\Models\QueryBuilder;

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

        $queryBuilder = new QueryBuilder($model);
        $joins = $queryBuilder->addRelations($this->relations);
        $queryBuilder->buildQuery();

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
            fn(array $row): object => Mapper::mapAssoc($model, $row),
            $results->fetchAllAssoc()
        );
    }
}
