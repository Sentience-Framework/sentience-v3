<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait Limit
{
    protected ?int $limit = null;

    public function limit(int $limit): static
    {
        $this->limit = $limit >= 0 ? $limit : null;

        return $this;
    }
}
