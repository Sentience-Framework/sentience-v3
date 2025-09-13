<?php

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Objects\Raw;
use Modules\Database\Queries\Traits\Columns;
use Modules\Database\Queries\Traits\Distinct;
use Modules\Database\Queries\Traits\GroupBy;
use Modules\Database\Queries\Traits\Having;
use Modules\Database\Queries\Traits\Joins;
use Modules\Database\Queries\Traits\Limit;
use Modules\Database\Queries\Traits\Offset;
use Modules\Database\Queries\Traits\OrderBy;
use Modules\Database\Queries\Traits\Where;
use Modules\Database\Results;

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

    public function execute(): Results
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
