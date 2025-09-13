<?php

namespace Modules\Database\Queries\Traits;

use DateTimeInterface;
use Modules\Database\Queries\Objects\QueryWithParams;

trait Having
{
    protected ?QueryWithParams $having = null;

    public function having(string $expression, bool|int|float|string|DateTimeInterface ...$values): static
    {
        $this->having = new QueryWithParams($expression, $values);

        return $this;
    }
}
