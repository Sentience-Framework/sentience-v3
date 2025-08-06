<?php

declare(strict_types=1);

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
