<?php

namespace sentience\Database\queries\traits;

use DateTime;
use sentience\Database\queries\objects\QueryWithParams;

trait Having
{
    protected ?QueryWithParams $having = null;

    public function having(string $expression, bool|int|float|string|DateTime ...$values): static
    {
        $this->having = new QueryWithParams($expression, $values);

        return $this;
    }
}
