<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

use DateTime;
use sentience\Database\Queries\Objects\QueryWithParams;

trait Having
{
    protected ?QueryWithParams $having = null;

    public function having(string $expression, bool|int|float|string|DateTime ...$values): static
    {
        $this->having = new QueryWithParams($expression, $values);

        return $this;
    }
}
