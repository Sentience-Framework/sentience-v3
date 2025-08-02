<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Traits\Columns;
use sentience\Database\Queries\Traits\Distinct;
use sentience\Database\Queries\Traits\GroupBy;
use sentience\Database\Queries\Traits\Having;
use sentience\Database\Queries\Traits\Joins;
use sentience\Database\Queries\Traits\Limit;
use sentience\Database\Queries\Traits\Offset;
use sentience\Database\Queries\Traits\OrderBy;
use sentience\Database\Queries\Traits\Table;
use sentience\Database\Queries\Traits\Where;

class Select extends Query
{
    use Columns;
    use Distinct;
    use GroupBy;
    use Having;
    use Joins;
    use Limit;
    use Offset;
    use OrderBy;
    use Table;
    use Where;

    public function build(): QueryWithParams
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

    public function count(null|string|array|Raw $column = null): int
    {
        $previousColumns = $this->columns;
        $previousDistinct = $this->distinct;

        $this->distinct = false;

        $countExpression = !is_null($column)
            ? ($previousDistinct ? 'DISTINCT ' : '') . $this->dialect->escapeIdentifier($column)
            : '*';

        $this->columns([
            Query::alias(
                Query::raw(
                    sprintf(
                        'COUNT(%s)',
                        $countExpression
                    )
                ),
                'count'
            )
        ]);

        $count = (int) $this->execute()->fetch()?->count ?? 0;

        $this->columns = $previousColumns;
        $this->distinct = $previousDistinct;

        return $count;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
