<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait Columns
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }
}
