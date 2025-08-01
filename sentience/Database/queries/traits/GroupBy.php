<?php

namespace sentience\Database\queries\traits;

trait GroupBy
{
    protected array $groupBy = [];

    public function groupBy(array $columns): static
    {
        $this->groupBy = $columns;

        return $this;
    }
}
