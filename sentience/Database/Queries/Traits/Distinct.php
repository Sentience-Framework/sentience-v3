<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait Distinct
{
    protected bool $distinct = false;

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }
}
