<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\SelectQuery;

trait WithTrait
{
    protected array $with = [];

    public function with(string $alias, QueryWithParams|SelectQuery $selectQuery): static
    {
        $this->with[$alias] = $selectQuery;

        return $this;
    }
}
