<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\queries\objects\Raw;
use sentience\Database\queries\traits\Columns;
use sentience\Database\queries\traits\Distinct;
use sentience\Database\queries\traits\GroupBy;
use sentience\Database\queries\traits\Having;
use sentience\Database\queries\traits\Joins;
use sentience\Database\queries\traits\Limit;
use sentience\Database\queries\traits\Offset;
use sentience\Database\queries\traits\OrderBy;
use sentience\Database\queries\traits\Table;
use sentience\Database\queries\traits\Where;

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
