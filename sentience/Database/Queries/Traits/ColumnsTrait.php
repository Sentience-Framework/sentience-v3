<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

trait ColumnsTrait
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        $this->columns = array_map(
            function (string|array|Alias|Raw $value, string $key): string|array|Alias|Raw {
                if (ctype_digit($key)) {
                    return $value;
                }

                if ($value instanceof Alias) {
                    if ($value->alias != $key) {
                        throw new QueryException('alias syntax should not be used with alias object');
                    }

                    return $value;
                }

                return Query::alias($value, $key);
            },
            $columns,
            array_keys($columns)
        );

        return $this;
    }
}
