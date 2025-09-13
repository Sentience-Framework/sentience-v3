<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\Columns;
use Sentience\Database\Queries\Traits\Distinct;
use Sentience\Database\Queries\Traits\GroupBy;
use Sentience\Database\Queries\Traits\Having;
use Sentience\Database\Queries\Traits\Joins;
use Sentience\Database\Queries\Traits\Limit;
use Sentience\Database\Queries\Traits\Offset;
use Sentience\Database\Queries\Traits\OrderBy;
use Sentience\Database\Queries\Traits\Where;
use Sentience\Database\Results;
use Sentience\Database\Results\ResultsInterface;

class Select extends ResultsQueryAbstract
{
    use Columns;
    use Distinct;
    use GroupBy;
    use Having;
    use Joins;
    use Limit;
    use Offset;
    use OrderBy;
    use Where;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->select([
            'distinct' => $this->distinct,
            'columns' => $this->columns,
            'table' => $this->table,
            'joins' => $this->joins,
            'where' => $this->where,
            'groupBy' => $this->groupBy,
            'having' => $this->having,
            'orderBy' => $this->orderBy,
            'limit' => $this->limit,
            'offset' => $this->offset
        ]);
    }

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultsInterface
    {
        return parent::execute();
    }

    public function count(null|string|array|Raw $column = null): int
    {
        $previousDistinct = $this->distinct;
        $previousColumns = $this->columns;
        $previousOrderBy = $this->orderBy;

        $this->distinct = false;
        $this->columns = [
            Query::alias(
                Query::raw(
                    sprintf(
                        'COUNT(%s)',
                        !is_null($column)
                        ? ($previousDistinct ? 'DISTINCT ' : '') . $this->dialect->escapeIdentifier($column)
                        : '*'
                    )
                ),
                'count'
            )
        ];
        $this->orderBy = [];

        $count = (int) $this->execute()->fetch()?->count ?? 0;

        $this->distinct = $previousDistinct;
        $this->columns = $previousColumns;
        $this->orderBy = $previousOrderBy;

        return $count;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
