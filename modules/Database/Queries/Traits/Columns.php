<?php

namespace Modules\Database\Queries\Traits;

trait Columns
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }
}
