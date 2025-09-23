<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\GroupByTrait;
use Sentience\Database\Queries\Traits\HavingTrait;
use Sentience\Database\Queries\Traits\JoinsTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultInterface;

class SelectQuery extends Query
{
    use ColumnsTrait;
    use DistinctTrait;
    use GroupByTrait;
    use HavingTrait;
    use JoinsTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use WhereTrait;

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

    public function execute(): ResultInterface
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

        $count = (int) $this->execute()->fetchObject()?->count ?? 0;

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
