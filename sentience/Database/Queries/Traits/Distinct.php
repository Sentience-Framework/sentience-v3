<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

trait Distinct
{
    protected bool $distinct = false;

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }
}
