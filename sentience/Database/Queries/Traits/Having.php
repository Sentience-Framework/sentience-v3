<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;

trait Having
{
    protected ?QueryWithParams $having = null;

    public function having(string $expression, bool|int|float|string|DateTimeInterface ...$values): static
    {
        $this->having = new QueryWithParams($expression, $values);

        return $this;
    }
}
